<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Rake;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class HistoricalRakeExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Rake>  $rakes
     */
    public function __construct(
        private readonly Collection $rakes,
    ) {}

    /**
     * @return Collection<int, Rake>
     */
    public function collection(): Collection
    {
        return $this->rakes;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Siding',
            'Loading Date',
            'Rake No',
            'Priority No',
            'RR No',
            'Wagons',
            'Loaded WT (MT)',
            'Under Load (MT)',
            'Over Load (MT)',
            'O/L Wagons',
            'Detention Hrs',
            'Shunting Hrs',
            'Total Amount (Rs)',
            'Destination',
            'IMWB Period',
            'Remarks',
            'Data Source',
        ];
    }

    /**
     * @param  Rake  $rake
     * @return array<int, string|int|float|null>
     */
    public function map($rake): array
    {
        return [
            $rake->siding?->name,
            $rake->loading_date?->toDateString(),
            $rake->rake_number,
            $rake->priority_number,
            $rake->rr_number,
            $rake->wagon_count,
            $rake->loaded_weight_mt !== null ? (float) $rake->loaded_weight_mt : null,
            $rake->under_load_mt !== null ? (float) $rake->under_load_mt : null,
            $rake->over_load_mt !== null ? (float) $rake->over_load_mt : null,
            $rake->overload_wagon_count,
            $rake->detention_hours !== null ? (float) $rake->detention_hours : null,
            $rake->shunting_hours !== null ? (float) $rake->shunting_hours : null,
            $rake->total_amount_rs !== null ? (float) $rake->total_amount_rs : null,
            $rake->destination,
            $rake->pakur_imwb_period,
            $rake->remarks,
            $rake->data_source,
        ];
    }
}
