<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\ModelFlags\Models\Flag as BaseFlag;

final class ModelFlag extends BaseFlag
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use LogsActivity;

    protected $table = 'model_flags';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'flaggable_type',
        'flaggable_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll();
    }
}
