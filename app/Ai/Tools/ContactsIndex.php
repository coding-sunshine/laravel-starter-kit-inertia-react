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
 * Tool for the Contact Assistant agent: search and list contacts in the current organization.
 */
final class ContactsIndex implements Tool
{
    public function description(): string
    {
        return 'Search or list contacts in the current organization. Use a query to search by name, company, or email; omit or leave empty to get recently updated contacts.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = $request->string('query')->trim();
        $limit = min(max((int) $request->integer('limit', 10), 1), 50);

        $builder = Contact::query()
            ->with(['contactEmails:contact_id,value', 'company:id,name'])
            ->orderByDesc('updated_at')
            ->limit($limit);

        if ($query !== '') {
            $builder->where(function ($q) use ($query): void {
                $like = '%'.Str::replace(['%', '_'], ['\\%', '\\_'], $query).'%';
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('company_name', 'like', $like)
                    ->orWhereHas('contactEmails', fn ($e) => $e->where('value', 'like', $like));
            });
        }

        $contacts = $builder->get(['id', 'organization_id', 'first_name', 'last_name', 'company_id', 'company_name', 'type', 'stage', 'updated_at']);

        if ($contacts->isEmpty()) {
            return 'No contacts found.';
        }

        $lines = $contacts->map(function (Contact $c): string {
            $name = mb_trim($c->first_name.' '.$c->last_name) ?: '—';
            $emails = $c->relationLoaded('contactEmails')
                ? $c->contactEmails->pluck('value')->take(2)->implode(', ')
                : '';
            $company = $c->company_name ?? ($c->company?->name ?? '');

            return sprintf(
                'ID %d: %s | Company: %s | Type: %s | Stage: %s | Email: %s | Updated: %s',
                $c->id,
                $name,
                $company ?: '—',
                $c->type ?? '—',
                $c->stage ?? '—',
                $emails ?: '—',
                $c->updated_at?->toDateString() ?? '—',
            );
        });

        return "Contacts (showing up to {$limit}):\n\n".$lines->implode("\n");
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Optional search string for name, company, or email.')
                ->default(''),
            'limit' => $schema->integer()
                ->description('Max number of contacts to return (1–50).')
                ->default(10),
        ];
    }
}
