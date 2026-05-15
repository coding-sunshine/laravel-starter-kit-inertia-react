<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\TransportWorkOrderRegistration;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class TransportWorkOrderRegistrationExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, TransportWorkOrderRegistration>  $rows
     */
    public function __construct(
        private readonly Collection $rows,
    ) {}

    /**
     * @return Collection<int, TransportWorkOrderRegistration>
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
            'Siding code',
            'Siding name',
            'WO no 1',
            'WO no 2',
            'Reference no',
            'Work order date',
            'Transporter name',
            'Trade name',
            'Legal name of business',
            'PAN',
            'GST no',
            'Status',
            'Active',
            'Email',
            'Vendor code',
            'Mobile 1',
            'Mobile 2',
            'Address',
            'Gramin / non-gramin',
        ];
    }

    /**
     * @param  TransportWorkOrderRegistration  $row
     * @return array<int, string|null>
     */
    public function map($row): array
    {
        return [
            $row->siding?->code,
            $row->siding?->name,
            $row->work_order_no_1,
            $row->work_order_no_2,
            $row->reference_no,
            $this->formatDate($row->work_order_date),
            $row->transporter_name,
            $row->trade_name,
            $row->legal_name_of_business,
            $row->pan_card,
            $row->gst_no,
            $row->status,
            ($row->is_active ?? true) ? 'Yes' : 'No',
            $row->email,
            $row->vendor_code,
            $row->mobile_1,
            $row->mobile_2,
            $row->address,
            $this->formatGraminOrNonGramin($row->gramin_or_non_gramin),
        ];
    }

    private function formatGraminOrNonGramin(?string $value): ?string
    {
        return match ($value) {
            TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_GRAMIN => 'Gramin',
            TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_NON_GRAMIN => 'Non-Gramin',
            default => $value,
        };
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
