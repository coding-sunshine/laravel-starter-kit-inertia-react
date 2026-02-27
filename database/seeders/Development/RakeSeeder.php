<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

final class RakeSeeder extends Seeder
{
    /**
     * @var array<string>
     */
    public array $dependencies = ['SidingSeeder', 'IndentSeeder'];

    public function run(): void
    {
        $sidings = Siding::query()->get();

        foreach ($sidings as $siding) {
            $indent = Indent::query()->where('siding_id', $siding->id)->first();

            for ($i = 1; $i <= 2; $i++) {
                Rake::query()->firstOrCreate([
                    'siding_id' => $siding->id,
                    'rake_number' => sprintf('RAKE-%s-%04d', $siding->code, $i),
                ], [
                    'indent_id' => $i === 1 ? $indent?->id : null,
                    'rake_type' => null,
                    'wagon_count' => null,
                    'state' => 'pending',
                    'placement_time' => Date::now()->subHours(2),
                    'loading_free_minutes' => 180,
                ]);
            }
        }
    }
}
