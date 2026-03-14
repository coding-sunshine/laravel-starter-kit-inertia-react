<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DealDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealDocument>
 */
final class DealDocumentFactory extends Factory
{
    protected $model = DealDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dealType = fake()->randomElement(['reservation', 'sale']);

        return [
            'deal_type' => $dealType,
            'deal_id' => fake()->numberBetween(1, 100),
            'document_type' => fake()->randomElement(['contract', 'invoice', 'id_doc', 'email', 'other']),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'file_path' => "deal-docs/{$dealType}/1/".fake()->uuid().'.pdf',
            'file_size' => fake()->optional()->numberBetween(10000, 5000000),
            'mime_type' => fake()->randomElement(['application/pdf', 'image/jpeg', 'image/png', 'application/msword']),
            'version' => 1,
            'access_roles' => null,
        ];
    }
}
