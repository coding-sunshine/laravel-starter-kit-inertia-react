<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Lot;
use App\Models\Project;
use Illuminate\Support\Str;
use Throwable;

final readonly class ImportInventoryAction
{
    /**
     * Import inventory (lots/projects) from a JSON or CSV payload.
     *
     * @param  array  $rows  Array of rows parsed from JSON or CSV
     * @param  string  $type  'lots' or 'projects'
     * @param  int  $orgId  Organization ID for scoping
     * @param  bool  $dryRun  If true, validate but don't persist
     * @return array{imported: int, updated: int, errors: list<string>}
     */
    public function handle(array $rows, string $type, int $orgId, bool $dryRun = false): array
    {
        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                if ($type === 'lots') {
                    $result = $this->processLot($row, $orgId, $dryRun);
                } else {
                    $result = $this->processProject($row, $orgId, $dryRun);
                }

                if ($result === 'created') {
                    $imported++;
                } elseif ($result === 'updated') {
                    $updated++;
                }
            } catch (Throwable $e) {
                $errors[] = "Row {$index}: {$e->getMessage()}";
            }
        }

        return compact('imported', 'updated', 'errors');
    }

    private function processLot(array $row, int $orgId, bool $dryRun): string
    {
        $externalId = $row['external_id'] ?? $row['id'] ?? null;

        $data = [
            'title' => $row['title'] ?? $row['name'] ?? null,
            'status' => $row['status'] ?? 'available',
            'land_price' => $row['land_price'] ?? $row['price'] ?? null,
            'build_price' => $row['build_price'] ?? null,
            'bedrooms' => $row['bedrooms'] ?? $row['beds'] ?? null,
            'bathrooms' => $row['bathrooms'] ?? $row['baths'] ?? null,
            'car_spaces' => $row['car_spaces'] ?? $row['garages'] ?? null,
            'land_area' => $row['land_area'] ?? $row['area'] ?? null,
            'address' => $row['address'] ?? null,
        ];

        if ($dryRun) {
            return 'created';
        }

        if ($externalId) {
            $existing = Lot::query()->where('legacy_id', $externalId)->first();
            if ($existing) {
                $existing->update($data);

                return 'updated';
            }
        }

        Lot::query()->create(array_merge($data, ['legacy_id' => $externalId]));

        return 'created';
    }

    private function processProject(array $row, int $orgId, bool $dryRun): string
    {
        $externalId = $row['external_id'] ?? $row['id'] ?? null;

        $data = [
            'name' => $row['name'] ?? $row['title'] ?? null,
            'status' => $row['status'] ?? 'active',
            'description' => $row['description'] ?? null,
            'from_price' => $row['from_price'] ?? $row['price'] ?? null,
        ];

        if ($dryRun) {
            return 'created';
        }

        if ($externalId) {
            $existing = Project::query()->where('legacy_id', $externalId)->first();
            if ($existing) {
                $existing->update($data);

                return 'updated';
            }
        }

        Project::query()->create(array_merge($data, [
            'organization_id' => $orgId,
            'legacy_id' => $externalId,
            'slug' => Str::slug($data['name'] ?? 'project-'.Str::random(6)),
        ]));

        return 'created';
    }
}
