<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AiBotCategory extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'order_column',
    ];

    /**
     * @return HasMany<AiBotPromptCommand, $this>
     */
    public function promptCommands(): HasMany
    {
        return $this->hasMany(AiBotPromptCommand::class);
    }

    /**
     * @return HasMany<AiBotBox, $this>
     */
    public function boxes(): HasMany
    {
        return $this->hasMany(AiBotBox::class);
    }
}
