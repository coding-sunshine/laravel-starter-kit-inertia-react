<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Contact;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * AI tool: search contacts and return C1-compatible ContactCard data.
 */
final class ContactSearchTool implements Tool
{
    public function description(): string
    {
        return 'Search CRM contacts by name, email, stage, or type. Returns contact data formatted for C1 ContactCard rendering.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = (string) ($request->input['query'] ?? '');
        $stage = $request->input['stage'] ?? null;
        $type = $request->input['type'] ?? null;
        $limit = (int) ($request->input['limit'] ?? 5);

        $q = Contact::query()
            ->with('assignedUser')
            ->limit(min($limit, 20));

        if ($query !== '') {
            $q->where(function ($b) use ($query) {
                $b->where('first_name', 'ilike', "%{$query}%")
                    ->orWhere('last_name', 'ilike', "%{$query}%")
                    ->orWhere('email', 'ilike', "%{$query}%")
                    ->orWhere('company_name', 'ilike', "%{$query}%");
            });
        }

        if ($stage !== null) {
            $q->where('stage', $stage);
        }

        if ($type !== null) {
            $q->where('type', $type);
        }

        $contacts = $q->get();

        $items = $contacts->map(fn (Contact $c) => [
            'id' => $c->id,
            'full_name' => mb_trim("{$c->first_name} {$c->last_name}"),
            'email' => $c->email,
            'phone' => $c->phone,
            'suburb' => $c->suburb,
            'state' => $c->state,
            'stage' => $c->stage,
            'lead_score' => $c->lead_score,
            'last_contacted_at' => $c->last_contacted_at?->toIso8601String(),
            'assigned_agent' => $c->assignedUser ? [
                'id' => $c->assignedUser->id,
                'name' => $c->assignedUser->name,
            ] : null,
            'tags' => [],
            'actions' => [
                ['label' => 'View Record', 'type' => 'link', 'href' => "/contacts/{$c->id}"],
                ['label' => 'Send Email', 'type' => 'action', 'action' => 'send_email', 'payload' => ['contact_id' => $c->id]],
                ['label' => 'Create Task', 'type' => 'action', 'action' => 'create_task', 'payload' => ['contact_id' => $c->id]],
            ],
        ])->all();

        $result = [
            'component' => 'ContactCard',
            'multiple' => true,
            'count' => count($items),
            'items' => $items,
        ];

        return Str::of(json_encode($result) ?: '{}');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search term (name, email, company)'),
            'stage' => $schema->string()->description('Filter by stage: new, qualified, hot, warm, cold, dead'),
            'type' => $schema->string()->description('Filter by type: lead, client, agent, partner'),
            'limit' => $schema->integer()->description('Max results (1-20, default 5)'),
        ];
    }
}
