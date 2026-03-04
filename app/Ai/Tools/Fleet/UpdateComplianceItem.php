<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Actions\Fleet\UpdateComplianceItemFromPromptAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class UpdateComplianceItem implements Tool
{
    public function __construct(
        private int $organizationId,
        private int $userId,
    ) {}

    public function description(): string
    {
        return 'Update a compliance item (e.g. mark as renewed, extend expiry date, add notes). Use when the user says they renewed a licence/MOT/insurance or want to update compliance status. Requires compliance_item_id. Provide at least one of: status (valid, expiring_soon, expired, renewed, revoked), expiry_date (Y-m-d), notes.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'compliance_item_id' => $schema->integer()->description('Compliance item ID (must belong to current organization)')->required(),
            'status' => $schema->string()->description('One of: valid, expiring_soon, expired, renewed, revoked'),
            'expiry_date' => $schema->string()->description('New expiry date (Y-m-d)'),
            'notes' => $schema->string()->description('Optional notes'),
        ];
    }

    public function handle(Request $request): string
    {
        $input = [
            'compliance_item_id' => (int) $request['compliance_item_id'],
            'status' => isset($request['status']) && $request['status'] !== '' ? (string) $request['status'] : null,
            'expiry_date' => isset($request['expiry_date']) && $request['expiry_date'] !== '' ? (string) $request['expiry_date'] : null,
            'notes' => property_exists($request, 'notes') ? ($request['notes'] ?? null) : null,
        ];

        try {
            $item = resolve(UpdateComplianceItemFromPromptAction::class)->handle(
                $this->organizationId,
                $this->userId,
                $input,
            );
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        return "Compliance item #{$item->id} updated: status={$item->status}, expiry={$item->expiry_date?->format('Y-m-d')}.";
    }
}
