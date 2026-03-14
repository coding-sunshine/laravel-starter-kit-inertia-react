<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\NurtureSequence;
use App\Models\SequenceEnrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Enroll a contact in a nurture sequence (durable, crash-safe via laravel-workflow).
 */
final readonly class EnrollInNurtureSequenceAction
{
    public function handle(Contact $contact, NurtureSequence $sequence): SequenceEnrollment
    {
        return DB::transaction(function () use ($contact, $sequence): SequenceEnrollment {
            $firstStep = $sequence->steps()->orderBy('step_order')->first();

            $enrollment = SequenceEnrollment::query()->updateOrCreate(
                [
                    'contact_id' => $contact->id,
                    'nurture_sequence_id' => $sequence->id,
                ],
                [
                    'current_step' => 0,
                    'status' => 'active',
                    'started_at' => Carbon::now(),
                    'next_run_at' => $firstStep
                        ? Carbon::now()->addDays($firstStep->delay_days)
                        : null,
                ]
            );

            // Start durable workflow for multi-day nurture sequence
            try {
                \Workflow\WorkflowStub::make(\App\Workflows\NurtureSequenceWorkflow::class)
                    ->start($enrollment->id);
            } catch (Throwable) {
                // Workflow not available (e.g. sync queue) — job scheduler will handle it
            }

            return $enrollment;
        });
    }
}
