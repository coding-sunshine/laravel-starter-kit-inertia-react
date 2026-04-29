<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionTimer extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;


    protected $fillable = [
        'section_name',
        'free_minutes',
        'warning_minutes',
        'penalty_applicable',
    ];

    protected $casts = [
        'penalty_applicable' => 'boolean',
    ];
}
