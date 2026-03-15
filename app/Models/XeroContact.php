<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $contact_id
 * @property int $xero_connection_id
 * @property string $xero_contact_id
 * @property string $sync_status
 * @property \Carbon\Carbon|null $last_synced_at
 * @property string|null $error_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class XeroContact extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'contact_id',
        'xero_connection_id',
        'xero_contact_id',
        'sync_status',
        'last_synced_at',
        'error_message',
    ];

    public function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function xeroConnection(): BelongsTo
    {
        return $this->belongsTo(XeroConnection::class);
    }
}
