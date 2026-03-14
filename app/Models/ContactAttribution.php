<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $contact_id
 * @property int|null $campaign_id
 * @property string|null $campaign_name
 * @property string|null $ad_id
 * @property string|null $ad_name
 * @property int|null $attributed_agent_contact_id
 * @property string|null $source
 * @property \Carbon\Carbon $attributed_at
 * @property int|null $organization_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class ContactAttribution extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'contact_id',
        'campaign_id',
        'campaign_name',
        'ad_id',
        'ad_name',
        'attributed_agent_contact_id',
        'source',
        'attributed_at',
        'organization_id',
    ];

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function agentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'attributed_agent_contact_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attributed_at' => 'datetime',
        ];
    }
}
