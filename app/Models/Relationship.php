<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Relationship extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'account_contact_id',
        'relation_contact_id',
        'type',
    ];

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function accountContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'account_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function relationContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'relation_contact_id');
    }
}

