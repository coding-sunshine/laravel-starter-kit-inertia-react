<?php

declare(strict_types=1);

namespace App\Exports;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use stdClass;

final class VehicleWorkorderTransporterExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, stdClass>  $rows
     */
    public function __construct(
        private readonly Collection $rows,
    ) {}

    /**
     * @return Collection<int, stdClass>
     */
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
            'Siding name',
            'Transport name',
            'WO no',
            'WO no 2',
            'Work order date',
            'Issue date',
            'Proprietor name',
            'Address',
            'Mobile',
            'Mobile 2',
            'Owner type',
            'PAN no',
            'GST no',
            'Total vehicles',
        ];
    }

    /**
     * @param  stdClass  $row
     * @return array<int, string|int|null>
     */
    public function map($row): array
    {
        return [
            $row->siding_name,
            $row->transport_name,
            $row->wo_no,
            $row->wo_no_2,
            $this->formatDate($row->work_order_date ?? null),
            $this->formatDate($row->issued_date ?? null),
            $row->proprietor_name,
            $row->address,
            $row->mobile_no_1,
            $row->mobile_no_2,
            $row->owner_type,
            $row->pan_no,
            $row->gst_no,
            (int) $row->vehicle_count,
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return is_string($value) ? $value : null;
    }
}
