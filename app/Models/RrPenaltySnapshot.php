<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RrPenaltySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'rr_document_id',
        'rake_id',
        'rake_charge_id',
        'penalty_code',
        'amount',
        'wagon_number',
        'wagon_sequence',
        'meta',
    ];

    /**
     * SQL that is true when the snapshot aliased as `$alias` belongs to an effective RR,
     * i.e. not to an original RR superseded by a diversion RR (see RrDocument::effectiveSql()).
     */
    public static function effectiveSql(string $alias = 'rr_penalty_snapshots'): string
    {
        return 'NOT EXISTS (SELECT 1 FROM rr_documents rr_orig WHERE rr_orig.id = '.$alias.'.rr_document_id AND NOT '.RrDocument::effectiveSql('rr_orig').')';
    }

    public function rrDocument(): BelongsTo
    {
        return $this->belongsTo(RrDocument::class);
    }

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function rakeCharge(): BelongsTo
    {
        return $this->belongsTo(RakeCharge::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }
}
