<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CampaignWebsiteTemplate extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'schema',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'schema' => 'array',
    ];
}

