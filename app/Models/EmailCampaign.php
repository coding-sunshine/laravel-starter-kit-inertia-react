<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Wildside\Userstamps\Userstamps;

final class EmailCampaign extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'organization_id',
        'mail_list_id',
        'name',
        'subject',
        'preview_text',
        'html_content',
        'plain_text',
        'from_name',
        'from_email',
        'status',
        'scheduled_at',
        'sent_at',
        'sent_count',
        'open_count',
        'click_count',
        'bounce_count',
        'metadata',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function mailList(): BelongsTo
    {
        return $this->belongsTo(MailList::class);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'sent_count' => 'integer',
            'open_count' => 'integer',
            'click_count' => 'integer',
            'bounce_count' => 'integer',
        ];
    }
}
