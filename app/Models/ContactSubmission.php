<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ContactSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\ContactSubmissionFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
    ];
}
