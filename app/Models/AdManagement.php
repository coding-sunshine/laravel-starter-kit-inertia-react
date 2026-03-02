<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

final class AdManagement extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use Userstamps;

    /**
     * @var string
     */
    protected $table = 'ad_managements';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'config',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
    ];
}

