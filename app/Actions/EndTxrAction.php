<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\Txr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class EndTxrAction
{
    public function __construct(
        private SyncTxrUnfitFlagsToWagonsAction $syncTxrUnfitFlagsToWagons,
    ) {}

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

            $this->syncTxrUnfitFlagsToWagons->handle($rake, $txr);

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
