<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class TraditionalSeedGenerator
{
    /**
     * Generate seed data using Faker (traditional method).
     *
     * @param  array<string, mixed>  $spec
     * @return array<int, array<string, mixed>>
     */
    public function generate(array $spec, int $count = 5): array
    {
        $fields = $spec['fields'] ?? [];
        $valueHints = $spec['value_hints'] ?? [];
        $records = [];

        for ($i = 0; $i < $count; $i++) {
            $record = [];

            foreach ($fields as $field => $fieldSpec) {
                if (in_array($field, ['id', 'created_at', 'updated_at'], true)) {
                    continue;
                }

                $record[$field] = $this->generateValue($field, $fieldSpec, $valueHints);
            }

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Generate a single value for a field.
     *
     * @param  array<string, mixed>  $fieldSpec
     * @param  array<string, mixed>  $valueHints
     */
    private function generateValue(string $field, array $fieldSpec, array $valueHints): mixed
    {
        // Check value hints first
        if (isset($valueHints[$field])) {
            $hint = $valueHints[$field];

            return match ($hint['type'] ?? 'string') {
                'email' => fake()->unique()->safeEmail(),
                'name' => fake()->name(),
                'url' => fake()->url(),
                'datetime' => fake()->dateTime()->format('Y-m-d H:i:s'),
                'password' => Hash::make('password'),
                'boolean' => fake()->boolean(),
                default => $hint['example'] ?? null,
            };
        }

        // Use default if available
        if ($fieldSpec['default'] !== null) {
            return $fieldSpec['default'];
        }

        // Generate based on field name patterns
        if (Str::contains($field, 'email')) {
            return fake()->unique()->safeEmail();
        }

        if (Str::contains($field, 'name')) {
            return fake()->name();
        }

        if (Str::contains($field, 'url') || Str::contains($field, 'link')) {
            return fake()->url();
        }

        if (Str::contains($field, 'password')) {
            return Hash::make('password');
        }

        if (Str::contains($field, 'phone')) {
            return fake()->phoneNumber();
        }

        if (Str::contains($field, 'address')) {
            return fake()->address();
        }

        if (Str::contains($field, 'description') || Str::contains($field, 'content') || Str::contains($field, 'body')) {
            return fake()->paragraph();
        }

        if (Str::contains($field, 'title')) {
            return fake()->sentence();
        }

        // Generate based on type
        $type = $fieldSpec['type'] ?? 'string';

        return match ($type) {
            'string' => fake()->words(3, true),
            'text' => fake()->paragraph(),
            'integer', 'bigint' => fake()->numberBetween(1, 1000),
            'boolean' => fake()->boolean(),
            'datetime', 'timestamp' => fake()->dateTime()->format('Y-m-d H:i:s'),
            'date' => fake()->date(),
            'time' => fake()->time(),
            'decimal', 'float', 'double' => fake()->randomFloat(2, 0, 1000),
            default => null,
        };
    }
}
