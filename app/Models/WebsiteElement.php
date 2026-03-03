<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WebsiteElement extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'website_page_id',
        'type',
        'config',
        'order',
    ];

    /**
     * @return BelongsTo<WebsitePage, $this>
     */
    public function websitePage(): BelongsTo
    {
        return $this->belongsTo(WebsitePage::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }
}
