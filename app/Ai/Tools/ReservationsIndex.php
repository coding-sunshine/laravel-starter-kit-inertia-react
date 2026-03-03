<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\PropertyReservation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class ReservationsIndex implements Tool
{
    public function description(): string
    {
        return 'List property reservations in the current organization. Optionally limit by count.';
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = min(max((int) $request->integer('limit', 10), 1), 50);

        $rows = PropertyReservation::query()
            ->with(['primaryContact:id,first_name,last_name', 'project:id,title', 'lot:id,title'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'primary_contact_id', 'project_id', 'lot_id', 'purchase_price', 'updated_at']);

        if ($rows->isEmpty()) {
            return 'No reservations found.';
        }

        $lines = $rows->map(function (PropertyReservation $r): string {
            $contact = $r->relationLoaded('primaryContact') && $r->primaryContact
                ? trim($r->primaryContact->first_name.' '.$r->primaryContact->last_name)
                : '—';
            $project = $r->relationLoaded('project') ? ($r->project?->title ?? '—') : '—';
            $lot = $r->relationLoaded('lot') ? ($r->lot?->title ?? '—') : '—';
            return sprintf(
                'ID %d: Contact %s | Project: %s | Lot: %s | Price: %s | Updated: %s',
                $r->id,
                $contact,
                \Illuminate\Support\Str::limit($project, 30),
                \Illuminate\Support\Str::limit($lot, 20),
                $r->purchase_price ? number_format($r->purchase_price) : '—',
                $r->updated_at?->toDateString() ?? '—',
            );
        });

        return "Reservations (up to {$limit}):\n\n".$lines->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Max results (1–50).')->default(10),
        ];
    }
}
