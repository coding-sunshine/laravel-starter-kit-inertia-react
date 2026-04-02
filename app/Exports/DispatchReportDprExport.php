<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\DispatchReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Throwable;

final class DispatchReportDprExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, DispatchReport>  $rows
     */
    public function __construct(
        private readonly Collection $rows,
    ) {}

    /**
     * @return Collection<int, DispatchReport>
     */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'SL NO',
            'REF',
            'E CHALLAN NO',
            'ISSUED ON',
            'TRUCK NO',
            'SHIFT',
            'DATE',
            'TRIPS',
            'WO.NO',
            'TRANSPORT NAME',
            'MINERAL WT',
            'GROSS WT',
            'TARE WT',
            'NET WT',
            'TYRES',
            'COAL TON VAR',
            'REACHED DATE & TIME',
            'WB',
            'TRIP ID NO',
            'SIDING',
        ];
    }

    /**
     * @param  DispatchReport  $row
     * @return list<int|float|string|null>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->ref_no ?? '—',
            $row->e_challan_no ?? '—',
            $this->formatDate($row->issued_on),
            $row->truck_no ?? '—',
            $row->shift ?? '—',
            $this->formatDate($row->date),
            $row->trips ?? '—',
            $row->wo_no ?? '—',
            $this->dveText($row->transport_name),
            $this->formatDecimal($row->mineral_wt),
            $this->formatDveDecimal($row->gross_wt_siding_rec_wt),
            $this->formatDveDecimal($row->tare_wt),
            $this->formatDveDecimal($row->net_wt_siding_rec_wt),
            $row->tyres ?? '—',
            $this->formatDveDecimal($row->coal_ton_variation),
            $this->formatDveDateTime($row->reached_datetime),
            $this->dveText($row->wb),
            $this->dveText($row->trip_id_no),
            $this->sidingLabel($row),
        ];
    }

    private function sidingLabel(DispatchReport $row): string
    {
        if ($row->relationLoaded('siding') && $row->siding !== null) {
            return "{$row->siding->name} ({$row->siding->code})";
        }

        return (string) $row->siding_id;
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d M Y');
        } catch (Throwable) {
            return '—';
        }
    }

    private function formatDveDateTime(mixed $value): string
    {
        if ($value === null) {
            return 'N/A';
        }

        try {
            return Carbon::parse($value)->format('d M Y H:i');
        } catch (Throwable) {
            return 'N/A';
        }
    }

    private function dveText(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return $value;
    }

    private function formatDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $n = is_numeric($value) ? (float) $value : null;
        if ($n === null) {
            return '—';
        }

        return number_format($n, 2, '.', '');
    }

    private function formatDveDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        $n = is_numeric($value) ? (float) $value : null;
        if ($n === null) {
            return 'N/A';
        }

        return number_format($n, 2, '.', '');
    }
}
