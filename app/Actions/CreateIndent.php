<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Indent;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CreateIndent - Create and manage coal indents
 *
 * Indents are requests for coal delivery to a siding.
 * Workflow: pending → approved → partial → fulfilled → closed
 */
final readonly class CreateIndent
{
    public function __construct(private UpdateStockLedger $stockLedger) {}

    /**
     * Create a new indent request
     *
     * @param array{
     *     siding_id: int,
     *     target_quantity_mt: float,
     *     required_by_date: string,
     *     remarks?: string,
     * } $data
     */
    public function handle(array $data, int $userId): Indent
    {
        return DB::transaction(function () use ($data, $userId): Indent {
            $siding = Siding::findOrFail($data['siding_id']);

            // Check stock availability
            $currentStock = $this->stockLedger->getCurrentBalance($siding->id);
            if ($currentStock < $data['target_quantity_mt']) {
                throw new InvalidArgumentException(
                    "Insufficient stock for indent. Available: {$currentStock} MT, Requested: {$data['target_quantity_mt']} MT"
                );
            }

            // Generate indent number
            $indentNumber = $this->generateIndentNumber($siding->id);

            // Create indent record
            $indent = Indent::create([
                'siding_id' => $siding->id,
                'indent_number' => $indentNumber,
                'target_quantity_mt' => $data['target_quantity_mt'],
                'allocated_quantity_mt' => 0,
                'state' => 'pending',
                'indent_date' => now(),
                'required_by_date' => $data['required_by_date'],
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);

            return $indent->refresh();
        });
    }

    /**
     * Approve an indent
     */
    public function approve(Indent $indent, int $userId): Indent
    {
        return DB::transaction(function () use ($indent, $userId): Indent {
            if ($indent->state !== 'pending') {
                throw new InvalidArgumentException('Only pending indents can be approved');
            }

            $indent->update([
                'state' => 'approved',
                'updated_by' => $userId,
            ]);

            return $indent->refresh();
        });
    }

    /**
     * Reject an indent
     */
    public function reject(Indent $indent, string $reason, int $userId): Indent
    {
        return DB::transaction(function () use ($indent, $reason, $userId): Indent {
            if ($indent->state !== 'pending') {
                throw new InvalidArgumentException('Only pending indents can be rejected');
            }

            $indent->update([
                'state' => 'cancelled',
                'remarks' => ($indent->remarks ? $indent->remarks.' | ' : '')."Rejected: {$reason}",
                'updated_by' => $userId,
            ]);

            return $indent->refresh();
        });
    }

    /**
     * Update allocated quantity as coal is loaded
     */
    public function allocateQuantity(Indent $indent, float $quantity, int $userId): Indent
    {
        return DB::transaction(function () use ($indent, $quantity, $userId): Indent {
            $allocated = $indent->allocated_quantity_mt + $quantity;

            if ($allocated > $indent->target_quantity_mt) {
                throw new InvalidArgumentException(
                    "Cannot allocate {$quantity} MT. Total would exceed target of {$indent->target_quantity_mt} MT"
                );
            }

            // Update state based on allocation
            $state = $allocated >= $indent->target_quantity_mt ? 'fulfilled' : 'partial';

            $indent->update([
                'allocated_quantity_mt' => $allocated,
                'state' => $state,
                'updated_by' => $userId,
            ]);

            return $indent->refresh();
        });
    }

    /**
     * Close a fulfilled indent
     */
    public function close(Indent $indent, int $userId): Indent
    {
        return DB::transaction(function () use ($indent, $userId): Indent {
            if ($indent->state !== 'fulfilled') {
                throw new InvalidArgumentException('Only fulfilled indents can be closed');
            }

            $indent->update([
                'state' => 'closed',
                'updated_by' => $userId,
            ]);

            return $indent->refresh();
        });
    }

    /**
     * Get pending indents for a siding
     */
    public function getPendingIndents(int $sidingId): Collection
    {
        return Indent::where('siding_id', $sidingId)
            ->whereIn('state', ['pending', 'approved'])
            ->orderBy('required_by_date', 'asc')
            ->get();
    }

    /**
     * Get open indents (not yet fulfilled)
     */
    public function getOpenIndents(int $sidingId): Collection
    {
        return Indent::where('siding_id', $sidingId)
            ->whereIn('state', ['approved', 'partial'])
            ->orderBy('required_by_date', 'asc')
            ->get();
    }

    /**
     * Get indent fulfillment progress
     */
    public function getFulfillmentProgress(Indent $indent): array
    {
        $remaining = $indent->target_quantity_mt - $indent->allocated_quantity_mt;

        return [
            'target_mt' => $indent->target_quantity_mt,
            'allocated_mt' => $indent->allocated_quantity_mt,
            'remaining_mt' => $remaining,
            'progress_percent' => round(($indent->allocated_quantity_mt / $indent->target_quantity_mt) * 100, 2),
            'days_remaining' => now()->diffInDays($indent->required_by_date),
            'is_overdue' => $remaining > 0 && $indent->required_by_date < now(),
        ];
    }

    /**
     * Generate unique indent number
     */
    private function generateIndentNumber(int $sidingId): string
    {
        $siding = Siding::find($sidingId);
        $sidingCode = $siding?->code ?? 'UNK';

        // Format: INDENT-CODE-YYYY-NNNN (e.g., INDENT-PKR-2026-0001)
        $count = Indent::where('siding_id', $sidingId)
            ->whereYear('indent_date', now()->year)
            ->count() + 1;

        return sprintf(
            'INDENT-%s-%s-%04d',
            $sidingCode,
            now()->format('Y'),
            $count
        );
    }
}
