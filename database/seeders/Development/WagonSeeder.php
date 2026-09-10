<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Rake;
use App\Models\Wagon;
use Illuminate\Database\Seeder;

final class WagonSeeder extends Seeder
{
    /**
     * @var array<string>
     */
    public array $dependencies = ['RakeSeeder'];

    public function run(): void
    {
        $rakes = Rake::query()->get();

        foreach ($rakes as $rake) {
            for ($seq = 1; $seq <= 5; $seq++) {
                $wagonNumber = sprintf('W-%s-%02d', $rake->rake_number, $seq);

                Wagon::query()->firstOrCreate([
                    'wagon_number' => $wagonNumber,
                ], [
                    'rake_id' => $rake->id,
                    'wagon_sequence' => $seq,
                    'wagon_type' => $rake->rake_type,
                    'tare_weight_mt' => null,
                    'pcc_weight_mt' => null,
                    'is_unfit' => false,
                    'state' => 'pending',
                ]);
            }
        }
    }
}
