<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Indent;
use App\Models\Siding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/** Indent seeder. Demo data from RakeManagementDemoSeeder. Exists for pre-commit. */
final class IndentSeeder extends Seeder
{
    /**
     * @var array<string>
     */
    public array $dependencies = ['SidingSeeder'];

    public function run(): void
    {
        $sidings = Siding::query()->get();

        foreach ($sidings as $siding) {
            for ($i = 1; $i <= 2; $i++) {
                Indent::query()->firstOrCreate([
                    'siding_id' => $siding->id,
                    'indent_number' => sprintf('IND-%s-%02d', $siding->code, $i),
                ], [
                    'state' => 'pending',
                    'indent_date' => Date::now()->subDays(2),
                    'required_by_date' => Date::now()->addDays(2),
                ]);
            }
        }
    }
}
