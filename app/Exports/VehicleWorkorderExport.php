<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\VehicleWorkorder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class VehicleWorkorderExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, VehicleWorkorder>  $rows
     */
    public function __construct(
        private readonly Collection $rows,
    ) {}

    /**
     * @return Collection<int, VehicleWorkorder>
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
            'Siding',
            'Vehicle No',
            'RCD PIN No',
            'Transport Name',
            'WO No',
            'WO No 2',
            'Work Order Date',
            'Issued Date',
            'Proprietor Name',
            'Represented By',
            'Place',
            'Address',
            'Tyres',
            'Tare Weight',
            'Mobile 1',
            'Mobile 2',
            'Owner Type',
            'Regd Date',
            'Permit Validity',
            'Tax Validity',
            'Fitness Validity',
            'Insurance Validity',
            'Maker Model',
            'Make',
            'Model',
            'Remarks',
            'Recommended By',
            'Referenced',
            'Local/Non-local',
            'PAN No',
            'GST No',
        ];
    }

    /**
     * @param  VehicleWorkorder  $wo
     * @return array<int, string|int|float|null>
     */
    public function map($wo): array
    {
        return [
            $wo->siding !== null ? "{$wo->siding->name} ({$wo->siding->code})" : null,
            $wo->vehicle_no,
            $wo->rcd_pin_no,
            $wo->transport_name,
            $wo->wo_no,
            $wo->wo_no_2,
            $wo->work_order_date?->toDateString(),
            $wo->issued_date?->toDateString(),
            $wo->proprietor_name,
            $wo->represented_by,
            $wo->place,
            $wo->address,
            $wo->tyres,
            $wo->tare_weight !== null ? (float) $wo->tare_weight : null,
            $wo->mobile_no_1,
            $wo->mobile_no_2,
            $wo->owner_type,
            $wo->regd_date?->toDateString(),
            $wo->permit_validity_date?->toDateString(),
            $wo->tax_validity_date?->toDateString(),
            $wo->fitness_validity_date?->toDateString(),
            $wo->insurance_validity_date?->toDateString(),
            $wo->maker_model,
            $wo->make,
            $wo->model,
            $wo->remarks,
            $wo->recommended_by,
            $wo->referenced,
            $wo->local_or_non_local,
            $wo->pan_no,
            $wo->gst_no,
        ];
    }
}
