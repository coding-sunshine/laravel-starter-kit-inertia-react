<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class BrochureProcessing extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'file_path',
        'type',
        'extracted_data',
        'status',
        'processed_by_user_id',
        'approved_by_user_id',
        'created_project_id',
        'created_lot_id',
        'admin_notes',
        'approved_at',
        'created_at_record',
    ];

    protected function casts(): array
    {
        return [
            'extracted_data' => 'array',
            'approved_at' => 'datetime',
            'created_at_record' => 'datetime',
        ];
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function createdProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'created_project_id');
    }

    public function createdLot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'created_lot_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCreated(): bool
    {
        return $this->status === 'created';
    }
}
