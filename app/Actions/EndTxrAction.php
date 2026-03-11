<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\Txr;
use App\Models\Wagon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class EndTxrAction
{
    /**
     * End the TXR for the given rake: sync wagon is_unfit from unfit logs,
     * set inspection_end_time and status completed.
     *
     * @param  array{remarks?: string|null}  $data
     * @return Txr the updated TXR
     *
     * @throws InvalidArgumentException if no TXR or already ended
     */
    public function handle(Rake $rake, array $data, int $userId): Txr
    {
        return DB::transaction(function () use ($rake, $data, $userId): Txr {
            $txr = $rake->txr;
            if ($txr === null) {
                throw new InvalidArgumentException('No TXR found for this rake.');
            }
            if ($txr->inspection_end_time !== null) {
                throw new InvalidArgumentException('TXR has already been ended.');
            }

            $unfitWagonIds = $txr->wagonUnfitLogs()->pluck('wagon_id')->all();

            Wagon::where('rake_id', $rake->id)->update(['is_unfit' => false]);
            if (! empty($unfitWagonIds)) {
                Wagon::whereIn('id', $unfitWagonIds)->update(['is_unfit' => true]);
            }

            $txr->update([
                'inspection_end_time' => now(),
                'status' => 'completed',
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => $userId,
            ]);

            return $txr->fresh();
        });
    }
}
