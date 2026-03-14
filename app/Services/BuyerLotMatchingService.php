<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Lot;
use Illuminate\Database\Eloquent\Collection;

/**
 * Buyer-lot matching service.
 * Matches a buyer contact to available lots based on their search preferences.
 * Returns PropertyCard-structured data for C1 rendering.
 */
final class BuyerLotMatchingService
{
    /**
     * Find matching lots for a buyer contact.
     *
     * @return array{component: string, multiple: bool, count: int, items: array<int, array<string, mixed>>}
     */
    public function matchForContact(Contact $contact, int $limit = 5): array
    {
        $q = Lot::query()
            ->with('project')
            ->where('is_archived', false)
            ->where('title_status', 'available')
            ->limit($limit);

        // Filter by most recent property search preferences if available
        $search = $contact->propertySearches()->latest()->first();

        if ($search !== null) {
            if ($search->max_budget !== null) {
                $q->where('price', '<=', $search->max_budget);
            }
            if ($search->min_bedrooms !== null) {
                $q->where('bedrooms', '>=', $search->min_bedrooms);
            }
            if ($search->preferred_suburb !== null) {
                $q->whereHas('project', fn ($b) => $b->where('suburb', 'ilike', "%{$search->preferred_suburb}%"));
            }
            if ($search->preferred_state !== null) {
                $q->whereHas('project', fn ($b) => $b->where('state', $search->preferred_state));
            }
        }

        $lots = $q->get();
        $items = $this->formatLots($lots);

        return [
            'component' => 'PropertyCard',
            'multiple' => true,
            'count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * Reverse match: find buyer contacts that match a specific lot.
     *
     * @return array{component: string, multiple: bool, count: int, items: array<int, array<string, mixed>>}
     */
    public function matchBuyersForLot(Lot $lot, int $limit = 10): array
    {
        $q = Contact::query()
            ->with('assignedUser')
            ->where('type', 'lead')
            ->whereIn('stage', ['hot', 'qualified', 'warm', 'new'])
            ->whereHas('propertySearches', function ($b) use ($lot) {
                if ($lot->price !== null) {
                    $b->where('max_budget', '>=', $lot->price);
                }
                if ($lot->bedrooms !== null) {
                    $b->where('min_bedrooms', '<=', $lot->bedrooms);
                }
            })
            ->limit($limit);

        $contacts = $q->get();

        $items = $contacts->map(fn (Contact $c) => [
            'id' => $c->id,
            'full_name' => mb_trim("{$c->first_name} {$c->last_name}"),
            'email' => $c->email,
            'phone' => $c->phone,
            'stage' => $c->stage,
            'lead_score' => $c->lead_score,
            'last_contacted_at' => $c->last_contacted_at?->toIso8601String(),
            'assigned_agent' => $c->assignedUser ? ['id' => $c->assignedUser->id, 'name' => $c->assignedUser->name] : null,
            'tags' => [],
            'actions' => [
                ['label' => 'View Record', 'type' => 'link', 'href' => "/contacts/{$c->id}"],
                ['label' => 'Present Lot', 'type' => 'action', 'action' => 'send_email', 'payload' => ['contact_id' => $c->id, 'lot_id' => $lot->id]],
            ],
        ])->all();

        return [
            'component' => 'ContactCard',
            'multiple' => true,
            'count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @param  Collection<int, Lot>  $lots
     * @return array<int, array<string, mixed>>
     */
    private function formatLots(Collection $lots): array
    {
        return $lots->map(fn (Lot $lot) => [
            'id' => $lot->id,
            'type' => 'lot',
            'title' => $lot->title ?? "Lot #{$lot->id}",
            'suburb' => $lot->project?->suburb,
            'state' => $lot->project?->state,
            'title_status' => $lot->title_status,
            'price' => $lot->price !== null ? (float) $lot->price : null,
            'bedrooms' => $lot->bedrooms,
            'bathrooms' => $lot->bathrooms,
            'car' => $lot->car,
            'total_m2' => $lot->total !== null ? (float) $lot->total : null,
            'project_title' => $lot->project?->title,
            'is_hot_property' => $lot->project?->is_hot_property ?? false,
            'actions' => [
                ['label' => 'View Project', 'type' => 'link', 'href' => "/projects/{$lot->project_id}"],
                ['label' => 'Reserve', 'type' => 'action', 'action' => 'start_reservation', 'payload' => ['lot_id' => $lot->id]],
            ],
        ])->all();
    }
}
