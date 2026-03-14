<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class BulkUpdateContactsAction
{
    /**
     * Mass-update contacts by id.
     *
     * @param  array<int>  $contactIds
     * @param  array<string, mixed>  $data
     * @return int Number of contacts updated
     */
    public function handle(array $contactIds, array $data, User $user): int
    {
        return DB::transaction(function () use ($contactIds, $data): int {
            $allowed = array_intersect_key($data, array_flip(['stage', 'extra_attributes', 'lead_score']));

            if (empty($allowed)) {
                return 0;
            }

            return Contact::query()
                ->whereIn('id', $contactIds)
                ->update($allowed);
        });
    }
}
