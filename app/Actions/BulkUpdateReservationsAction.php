<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PropertyReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class BulkUpdateReservationsAction
{
    /**
     * Mass-update reservations by id.
     *
     * @param  array<int>  $reservationIds
     * @param  array<string, mixed>  $data
     * @return int Number of reservations updated
     */
    public function handle(array $reservationIds, array $data, User $user): int
    {
        return DB::transaction(function () use ($reservationIds, $data): int {
            $allowed = array_intersect_key($data, array_flip(['stage', 'status']));

            if (empty($allowed)) {
                return 0;
            }

            return PropertyReservation::query()
                ->whereIn('id', $reservationIds)
                ->update($allowed);
        });
    }
}
