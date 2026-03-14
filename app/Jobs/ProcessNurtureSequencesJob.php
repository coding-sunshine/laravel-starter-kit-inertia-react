<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SequenceEnrollment;
use App\Models\SequenceStep;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Scheduled job that processes due nurture sequence steps for all active enrollments.
 * Run hourly via scheduler in routes/console.php.
 */
final class ProcessNurtureSequencesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        SequenceEnrollment::query()
            ->where('status', 'active')
            ->where('next_run_at', '<=', Carbon::now())
            ->with(['contact', 'sequence.steps'])
            ->chunkById(50, function ($enrollments): void {
                foreach ($enrollments as $enrollment) {
                    $this->processEnrollment($enrollment);
                }
            });
    }

    private function processEnrollment(SequenceEnrollment $enrollment): void
    {
        $steps = $enrollment->sequence->steps->sortBy('step_order')->values();
        $stepIndex = $enrollment->current_step;

        if ($stepIndex >= $steps->count()) {
            $enrollment->update(['status' => 'completed', 'completed_at' => Carbon::now()]);

            return;
        }

        /** @var SequenceStep $step */
        $step = $steps[$stepIndex];

        // Log the nurture action
        activity()
            ->performedOn($enrollment->contact)
            ->withProperties(['channel' => $step->channel, 'step_order' => $step->step_order])
            ->log("Nurture step dispatched: {$step->channel}");

        // Advance enrollment
        $nextIndex = $stepIndex + 1;
        $nextStep = $steps[$nextIndex] ?? null;

        $enrollment->update([
            'current_step' => $nextIndex,
            'next_run_at' => $nextStep ? Carbon::now()->addDays($nextStep->delay_days) : null,
            'status' => $nextStep ? 'active' : 'completed',
            'completed_at' => $nextStep ? null : Carbon::now(),
        ]);
    }
}
