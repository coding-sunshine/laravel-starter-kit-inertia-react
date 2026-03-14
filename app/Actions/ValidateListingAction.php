<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Lot;
use App\Models\Project;

final readonly class ValidateListingAction
{
    /**
     * Validate a listing (Lot or Project) for completeness and correctness.
     * Returns ['valid' => bool, 'errors' => array, 'warnings' => array].
     *
     * @return array{valid: bool, errors: list<string>, warnings: list<string>}
     */
    public function handle(Lot|Project $listing): array
    {
        $errors = [];
        $warnings = [];

        if ($listing instanceof Lot) {
            $this->validateLot($listing, $errors, $warnings);
        } else {
            $this->validateProject($listing, $errors, $warnings);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function validateLot(Lot $lot, array &$errors, array &$warnings): void
    {
        if (empty($lot->title)) {
            $errors[] = 'Lot title is required.';
        }

        if (empty($lot->status)) {
            $errors[] = 'Lot status is required.';
        }

        if (empty($lot->land_price) && empty($lot->build_price)) {
            $warnings[] = 'No price set for this lot.';
        }

        if (empty($lot->project_id)) {
            $errors[] = 'Lot must belong to a project.';
        }

        if (empty($lot->bedrooms) && empty($lot->land_area)) {
            $warnings[] = 'Consider adding bedrooms or land area for better matching.';
        }

        if (empty($lot->suburb_id) && empty($lot->address)) {
            $warnings[] = 'Location details are missing (suburb or address).';
        }
    }

    private function validateProject(Project $project, array &$errors, array &$warnings): void
    {
        if (empty($project->name)) {
            $errors[] = 'Project name is required.';
        }

        if (empty($project->status)) {
            $errors[] = 'Project status is required.';
        }

        if (empty($project->developer_id)) {
            $warnings[] = 'No developer linked to this project.';
        }

        if (empty($project->description)) {
            $warnings[] = 'Project description is missing — adds credibility for buyers.';
        }

        if (empty($project->state_id) && empty($project->suburb_id)) {
            $warnings[] = 'Location details are missing (state or suburb).';
        }
    }
}
