<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;

final class SeedingMetrics
{
    private array $metrics = [];

    /**
     * Start timing a seeder.
     */
    public function startSeeder(string $seederName): void
    {
        $this->metrics[$seederName] = [
            'name' => $seederName,
            'started_at' => microtime(true),
            'records_created' => [],
            'warnings' => [],
            'errors' => [],
        ];
    }

    /**
     * End timing a seeder.
     */
    public function endSeeder(string $seederName): void
    {
        if (! isset($this->metrics[$seederName])) {
            return;
        }

        $this->metrics[$seederName]['ended_at'] = microtime(true);
        $this->metrics[$seederName]['duration'] = $this->metrics[$seederName]['ended_at'] - $this->metrics[$seederName]['started_at'];
    }

    /**
     * Record records created for a model.
     */
    public function recordCreated(string $seederName, string $model, int $count): void
    {
        if (! isset($this->metrics[$seederName])) {
            return;
        }

        $this->metrics[$seederName]['records_created'][$model] = ($this->metrics[$seederName]['records_created'][$model] ?? 0) + $count;
    }

    /**
     * Add warning.
     */
    public function addWarning(string $seederName, string $message): void
    {
        if (! isset($this->metrics[$seederName])) {
            return;
        }

        $this->metrics[$seederName]['warnings'][] = $message;
    }

    /**
     * Add error.
     */
    public function addError(string $seederName, string $message): void
    {
        if (! isset($this->metrics[$seederName])) {
            return;
        }

        $this->metrics[$seederName]['errors'][] = $message;
    }

    /**
     * Get all metrics.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get summary.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $totalDuration = 0;
        $totalRecords = 0;
        $totalWarnings = 0;
        $totalErrors = 0;

        foreach ($this->metrics as $metric) {
            $totalDuration += $metric['duration'] ?? 0;
            $totalRecords += array_sum($metric['records_created'] ?? []);
            $totalWarnings += count($metric['warnings'] ?? []);
            $totalErrors += count($metric['errors'] ?? []);
        }

        return [
            'seeders_run' => count($this->metrics),
            'total_duration' => round($totalDuration, 2),
            'total_records' => $totalRecords,
            'total_warnings' => $totalWarnings,
            'total_errors' => $totalErrors,
        ];
    }

    /**
     * Save metrics to file.
     */
    public function save(string $path): void
    {
        $data = [
            'timestamp' => now()->toIso8601String(),
            'summary' => $this->getSummary(),
            'seeders' => $this->metrics,
        ];

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Clear metrics.
     */
    public function clear(): void
    {
        $this->metrics = [];
    }
}
