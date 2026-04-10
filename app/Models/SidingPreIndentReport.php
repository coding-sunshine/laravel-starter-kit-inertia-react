<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SidingPreIndentReport extends Model
{
    /** @use HasFactory<\Database\Factories\SidingPreIndentReportFactory> */
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'siding_id',
        'report_date',
        'total_indent_raised',
        'indent_available',
        'loading_status_text',
        'indent_details_text',
    ];

    /**
     * @return BelongsTo<Siding, $this>
     */
    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function headingLine(): string
    {
        if ($this->siding !== null) {
            $base = preg_replace('/\s+Siding$/i', '', $this->siding->name) ?? '';

            return mb_strtoupper(mb_trim($base)).' RAILWAY SIDING';
        }

        return 'RAILWAY SIDING';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
        ];
    }
}
