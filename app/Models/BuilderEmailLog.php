<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $contact_id
 * @property int|null $project_id
 * @property int|null $sent_by
 * @property string $template_type
 * @property string $recipient_email
 * @property string|null $recipient_name
 * @property string $subject
 * @property array|null $payload
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class BuilderEmailLog extends Model
{
    use BelongsToOrganization;

    public const TEMPLATE_PRICE_LIST = 'price_list';

    public const TEMPLATE_MORE_INFO = 'more_info';

    public const TEMPLATE_HOLD_REQUEST = 'hold_request';

    public const TEMPLATE_PROPERTY_REQUEST = 'property_request';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'contact_id',
        'project_id',
        'sent_by',
        'template_type',
        'recipient_email',
        'recipient_name',
        'subject',
        'payload',
        'status',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
