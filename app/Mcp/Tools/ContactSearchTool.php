<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Contact;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class ContactSearchTool extends Tool
{
    protected string $name = 'contacts_search';

    protected string $title = 'Search contacts';

    protected string $description = 'Search CRM contacts by name, email, stage, or type. Returns ContactCard-structured data for C1 generative UI rendering.';

    public function handle(Request $request): Response
    {
        $query = (string) ($request->get('query') ?? '');
        $stage = $request->get('stage');
        $type = $request->get('type');
        $limit = (int) ($request->get('limit') ?? 5);

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
            'assigned_agent' => $c->assignedUser ? ['id' => $c->assignedUser->id, 'name' => $c->assignedUser->name] : null,
            'tags' => [],
            'actions' => [
                ['label' => 'View Record', 'type' => 'link', 'href' => "/contacts/{$c->id}"],
                ['label' => 'Send Email', 'type' => 'action', 'action' => 'send_email', 'payload' => ['contact_id' => $c->id]],
                ['label' => 'Create Task', 'type' => 'action', 'action' => 'create_task', 'payload' => ['contact_id' => $c->id]],
            ],
        ])->all();

        return Response::json([
            'component' => 'ContactCard',
            'multiple' => true,
            'count' => count($items),
            'items' => $items,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search term (name, email, company)')->nullable(),
            'stage' => $schema->string()->description('Filter by stage: new, qualified, hot, warm, cold, dead')->nullable(),
            'type' => $schema->string()->description('Filter by type: lead, client, agent, partner')->nullable(),
            'limit' => $schema->integer()->description('Max results (1-20, default 5)')->nullable(),
        ];
    }
}
