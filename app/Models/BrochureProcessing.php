<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BrochureProcessing extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'batch_id',
        'file_path',
        'type',
        'extracted_data',
        'status',
        'queue_status',
        'processed_by_user_id',
        'approved_by_user_id',
        'created_project_id',
        'created_lot_id',
        'admin_notes',
        'approved_at',
        'processing_started_at',
        'processing_completed_at',
        'created_at_record',
    ];

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

    public function isQueuePending(): bool
    {
        return $this->queue_status === 'pending';
    }

    public function isQueueProcessing(): bool
    {
        return $this->queue_status === 'processing';
    }

    public function isQueueCompleted(): bool
    {
        return $this->queue_status === 'completed';
    }

    public function isQueueFailed(): bool
    {
        return $this->queue_status === 'failed';
    }

    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeInQueue($query, ?string $queueStatus = null)
    {
        if ($queueStatus) {
            return $query->where('queue_status', $queueStatus);
        }

        return $query->whereNotNull('batch_id');
    }

    public function getFileNameAttribute(): string
    {
        return basename($this->file_path);
    }

    public function getFileSizeAttribute(): ?int
    {
        if (! $this->file_path || ! \Storage::exists($this->file_path)) {
            return null;
        }

        return \Storage::size($this->file_path);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $size = $this->file_size;

        if (! $size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2).' '.$units[$unitIndex];
    }

    protected function casts(): array
    {
        return [
            'extracted_data' => 'array',
            'approved_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'processing_completed_at' => 'datetime',
            'created_at_record' => 'datetime',
        ];
    }
}
