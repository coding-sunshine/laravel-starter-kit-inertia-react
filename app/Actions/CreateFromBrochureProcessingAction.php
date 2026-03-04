<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\BrochureProcessing;
use App\Models\Developer;
use App\Models\Lot;
use App\Models\Project;
use App\Models\Projecttype;
use Illuminate\Support\Facades\DB;

final readonly class CreateFromBrochureProcessingAction
{
    /**
     * Create a project or lot from brochure processing data.
     */
    public function handle(BrochureProcessing $processing): array
    {
        try {
            $result = DB::transaction(function () use ($processing) {
                $data = $processing->extracted_data;

                if ($processing->type === 'project') {
                    $record = $this->createProject($processing, $data);
                    return [
                        'success' => true,
                        'type' => 'project',
                        'id' => $record->id,
                        'title' => $record->title,
                        'record' => $record
                    ];
                } else {
                    $record = $this->createLot($processing, $data);
                    return [
                        'success' => true,
                        'type' => 'lot',
                        'id' => $record->id,
                        'title' => $record->title,
                        'record' => $record
                    ];
                }
            });

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function createProject(BrochureProcessing $processing, array $data): Project
    {
        // Find or create developer if specified
        $developerId = null;
        if (!empty($data['developer'])) {
            $developer = Developer::firstOrCreate([
                'name' => $data['developer'],
                'organization_id' => $processing->organization_id,
            ]);
            $developerId = $developer->id;
        }

        // Find or create project type if specified (table column is "title", not "name")
        $projecttypeId = null;
        if (!empty($data['projecttype'])) {
            $projecttype = Projecttype::firstOrCreate([
                'organization_id' => $processing->organization_id,
                'title' => (string) $data['projecttype'],
            ]);
            $projecttypeId = $projecttype->id;
        }

        $project = Project::create([
            'organization_id' => $processing->organization_id,
            'title' => $data['title'] ?? 'Untitled Project',
            'estate' => $data['estate'] ?? null,
            'stage' => $data['stage'] ?? null,
            'description' => $data['description'] ?? null,
            'developer_id' => $developerId,
            'projecttype_id' => $projecttypeId,
            'total_lots' => $data['total_lots'] ?? null,
            'min_price' => $this->parsePrice($data['min_price'] ?? null),
            'max_price' => $this->parsePrice($data['max_price'] ?? null),
            'is_archived' => false,
        ]);

        // Update processing record
        $processing->update([
            'status' => 'created',
            'created_project_id' => $project->id,
            'created_at_record' => now(),
        ]);

        return $project;
    }

    private function createLot(BrochureProcessing $processing, array $data): Lot
    {
        // Try to find the project by title if specified
        $projectId = null;
        if (!empty($data['project_title'])) {
            $project = Project::where('organization_id', $processing->organization_id)
                ->where('title', 'ILIKE', '%' . $data['project_title'] . '%')
                ->first();

            if ($project) {
                $projectId = $project->id;
            }
        }

        $lot = Lot::create([
            'organization_id' => $processing->organization_id,
            'project_id' => $projectId,
            'title' => $data['title'] ?? 'Untitled Lot',
            'stage' => $data['stage'] ?? null,
            'price' => $this->parsePrice($data['price'] ?? null),
            'land_price' => $this->parsePrice($data['land_price'] ?? null),
            'bedrooms' => $this->parseInteger($data['bedrooms'] ?? null),
            'bathrooms' => $this->parseInteger($data['bathrooms'] ?? null),
            'land_size' => $data['land_size'] ?? null,
            'is_archived' => false,
        ]);

        // Update processing record
        $processing->update([
            'status' => 'created',
            'created_lot_id' => $lot->id,
            'created_at_record' => now(),
        ]);

        return $lot;
    }

    private function parsePrice(mixed $price): ?float
    {
        if (empty($price)) {
            return null;
        }

        // Convert to string first to handle Stringable objects
        $priceStr = (string) $price;

        // Remove non-numeric characters except decimal points
        $cleaned = preg_replace('/[^\d.]/', '', $priceStr);

        return $cleaned !== '' ? (float) $cleaned : null;
    }

    private function parseInteger(mixed $value): ?int
    {
        if (empty($value)) {
            return null;
        }

        // Convert to string first to handle Stringable objects
        $valueStr = (string) $value;

        $cleaned = preg_replace('/[^\d]/', '', $valueStr);

        return $cleaned !== '' ? (int) $cleaned : null;
    }
}
