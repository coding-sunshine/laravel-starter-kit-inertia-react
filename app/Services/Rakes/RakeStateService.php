<?php

declare(strict_types=1);

namespace App\Services\Rakes;

use App\Models\Rake;

final readonly class RakeStateService
{
    public function getAvailableTransitions(Rake $rake): array
    {
        $currentState = $rake->state;
        $transitions = [];

        switch ($currentState) {
            case 'pending':
                $transitions[] = 'txr_in_progress';
                break;

            case 'txr_in_progress':
                $transitions[] = 'txr_completed';
                break;

            case 'txr_completed':
                $transitions[] = 'loading';
                break;

            case 'loading':
                if ($this->isAllWagonsLoaded($rake)) {
                    $transitions[] = 'loading_completed';
                }
                break;

            case 'loading_completed':
                $transitions[] = 'guard_approved';
                $transitions[] = 'guard_rejected';
                break;

            case 'guard_approved':
                $transitions[] = 'weighment_completed';
                break;

            case 'guard_rejected':
                // Can only go back to loading to fix issues
                $transitions[] = 'loading';
                break;

            case 'weighment_completed':
                $transitions[] = 'rr_generated';
                break;

            case 'rr_generated':
                $transitions[] = 'closed';
                break;

            case 'closed':
                // Terminal state - no transitions
                break;
        }

        return $transitions;
    }

    public function canTransitionTo(Rake $rake, string $newState): bool
    {
        return in_array($newState, $this->getAvailableTransitions($rake));
    }

    public function transitionTo(Rake $rake, string $newState): bool
    {
        if (! $this->canTransitionTo($rake, $newState)) {
            return false;
        }

        $rake->update(['state' => $newState]);

        return true;
    }

    public function getWorkflowProgress(Rake $rake): array
    {
        $states = [
            'pending' => ['order' => 0, 'name' => 'Pending', 'completed' => false],
            'txr_in_progress' => ['order' => 1, 'name' => 'TXR In Progress', 'completed' => false],
            'txr_completed' => ['order' => 2, 'name' => 'TXR Completed', 'completed' => true],
            'loading' => ['order' => 3, 'name' => 'Loading', 'completed' => false],
            'loading_completed' => ['order' => 4, 'name' => 'Loading Completed', 'completed' => true],
            'guard_approved' => ['order' => 5, 'name' => 'Guard Approved', 'completed' => true],
            'guard_rejected' => ['order' => 5, 'name' => 'Guard Rejected', 'completed' => false],
            'weighment_completed' => ['order' => 6, 'name' => 'Weighment Completed', 'completed' => true],
            'rr_generated' => ['order' => 7, 'name' => 'RR Generated', 'completed' => true],
            'closed' => ['order' => 8, 'name' => 'Closed', 'completed' => true],
        ];

        $currentState = $rake->state;
        $currentOrder = $states[$currentState]['order'] ?? 0;

        // Mark all previous states as completed
        foreach ($states as $state => $data) {
            if ($data['order'] < $currentOrder) {
                $states[$state]['completed'] = true;
            } elseif ($data['order'] > $currentOrder) {
                $states[$state]['completed'] = false;
            }
        }

        return [
            'current_state' => $currentState,
            'current_order' => $currentOrder,
            'states' => $states,
            'progress_percentage' => ($currentOrder / 8) * 100,
        ];
    }

    public function isWorkflowComplete(Rake $rake): bool
    {
        return in_array($rake->state, ['rr_generated', 'closed']);
    }

    public function isWorkflowBlocked(Rake $rake): bool
    {
        return $rake->state === 'guard_rejected';
    }

    public function getStateDescription(string $state): string
    {
        $descriptions = [
            'pending' => 'Rake is pending TXR inspection',
            'txr_in_progress' => 'Train examination is in progress',
            'txr_completed' => 'Train examination completed successfully',
            'loading' => 'Wagons are being loaded',
            'loading_completed' => 'All fit wagons loaded (quantity recorded)',
            'guard_approved' => 'Guard has approved for movement',
            'guard_rejected' => 'Guard rejected - workflow blocked',
            'weighment_completed' => 'Rake weighment completed',
            'rr_generated' => 'Railway receipt document generated',
            'closed' => 'Rake workflow completed and closed',
        ];

        return $descriptions[$state] ?? 'Unknown state';
    }

    private function isAllWagonsLoaded(Rake $rake): bool
    {
        return $rake->allFitWagonsHavePositiveLoading();
    }
}
