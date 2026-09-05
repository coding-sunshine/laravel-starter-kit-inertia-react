<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

final class RakesExport implements FromCollection, WithHeadings
{
    /**
     * @param  Collection<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private readonly Collection $rows,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Rake Number',
            'Siding Code',
            'Siding Name',
            'Loading Date',
            'Placement Time',
            'Dispatch Time',
            'Wagon Count',
            'Loaded Weight (MT)',
            'Under Load (MT)',
            'Over Load (MT)',
            'Detention Hours',
            'Shunting Hours',
            'Total Amount (Rs)',
            'RR Number',
            'State',
            'Data Source',
            'Created At',
        ];
    }
}
