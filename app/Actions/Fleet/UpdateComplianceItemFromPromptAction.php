<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Models\Fleet\ComplianceItem;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateComplianceItemFromPromptAction
{
    /** Allowed status values for compliance items. */
    private const array STATUSES = ['valid', 'expiring_soon', 'expired', 'renewed', 'revoked'];

    /**
     * Update a compliance item (e.g. mark as renewed, extend expiry) from AI/conversation input.
     *
     * @param  array{compliance_item_id: int, status?: string, expiry_date?: string, notes?: string}  $input
     */
    public function handle(int $organizationId, int $userId, array $input): ComplianceItem
    {
        $id = (int) $input['compliance_item_id'];
        $item = ComplianceItem::query()->withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->find($id);

        throw_if($item === null, InvalidArgumentException::class, "Compliance item {$id} not found or does not belong to this organization.");

        $updates = [];

        if (isset($input['status']) && in_array($input['status'], self::STATUSES, true)) {
            $updates['status'] = $input['status'];
        }

        if (isset($input['expiry_date']) && $input['expiry_date'] !== '') {
            $updates['expiry_date'] = $input['expiry_date'];
        }

        if (array_key_exists('notes', $input)) {
            $updates['notes'] = $input['notes'] ?? null;
        }

        throw_if($updates === [], InvalidArgumentException::class, 'Provide at least one of: status, expiry_date, notes.');

        return DB::transaction(function () use ($item, $updates): ComplianceItem {
            TenantContext::set($item->organization_id);
            $item->update($updates);

            return $item->fresh();
        });
    }
}
