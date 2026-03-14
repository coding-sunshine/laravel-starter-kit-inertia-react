<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Models\SequenceEnrollment;
use App\Models\SequenceStep;
use Illuminate\Support\Carbon;
use Workflow\Activity;

/**
 * Activity that processes the current nurture step for an enrollment.
 * Sends the appropriate message (email/SMS/task) and advances to the next step.
 */
final class SendNurtureStepActivity extends Activity
{
    public function execute(int $enrollmentId): string
    {
        $enrollment = SequenceEnrollment::query()
            ->with(['contact', 'sequence.steps'])
            ->find($enrollmentId);

        if (! $enrollment || $enrollment->status !== 'active') {
            return 'skipped: enrollment not active';
        }

        $steps = $enrollment->sequence->steps->sortBy('step_order')->values();
        $stepIndex = $enrollment->current_step;

        if ($stepIndex >= $steps->count()) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => Carbon::now(),
            ]);

            return 'completed: all steps sent';
        }

        /** @var SequenceStep $step */
        $step = $steps[$stepIndex];
        $contact = $enrollment->contact;

        // Dispatch the step action
        $this->dispatchStep($step, $contact);

        // Advance to next step
        $nextIndex = $stepIndex + 1;
        $nextStep = $steps[$nextIndex] ?? null;

        $enrollment->update([
            'current_step' => $nextIndex,
            'next_run_at' => $nextStep
                ? Carbon::now()->addDays($nextStep->delay_days)
                : null,
            'status' => $nextStep ? 'active' : 'completed',
            'completed_at' => $nextStep ? null : Carbon::now(),
        ]);

        return "sent step {$step->step_order} ({$step->channel}) for contact {$contact->id}";
    }

    private function dispatchStep(SequenceStep $step, \App\Models\Contact $contact): void
    {
        // Log the nurture step action for the contact
        activity()
            ->performedOn($contact)
            ->withProperties([
                'channel' => $step->channel,
                'subject' => $step->subject,
                'step_order' => $step->step_order,
            ])
            ->log("Nurture step sent: {$step->channel} - ".($step->subject ?? 'no subject'));

        // In production: dispatch email/SMS/task based on channel
        // Email: Mail::to($contact->primaryEmail)->send(new NurtureEmail($step, $contact))
        // SMS: Sms::to($contact->primaryPhone)->send($step->template_body)
        // Task: Task::create([...])
    }
}
