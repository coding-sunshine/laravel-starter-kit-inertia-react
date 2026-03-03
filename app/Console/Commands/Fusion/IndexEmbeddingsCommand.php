<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Contact;
use App\Models\EmbeddingDocument;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Services\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Pgvector\Laravel\Vector;
use Throwable;

final class IndexEmbeddingsCommand extends Command
{
    protected $signature = 'fusion:index-embeddings
                            {--organization-id= : Index only this organization}
                            {--chunk=50 : Contacts per batch for embedding}
                            {--force : Run even when not PostgreSQL}';

    protected $description = 'Index contact summaries into embedding_documents for RAG (Step 6). Requires PostgreSQL and AI embeddings configured.';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql' && ! $this->option('force')) {
            $this->warn('embedding_documents requires PostgreSQL. Skipping.');

            return self::SUCCESS;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('embedding_documents')) {
            $this->error('Table embedding_documents not found. Run migrations.');

            return self::FAILURE;
        }

        $orgId = $this->option('organization-id');
        $organizations = $orgId
            ? Organization::query()->where('id', (int) $orgId)->get()
            : Organization::query()->orderBy('id')->get();

        if ($organizations->isEmpty()) {
            $this->error('No organization(s) found.');

            return self::FAILURE;
        }

        $chunk = (int) $this->option('chunk');
        $indexed = 0;

        foreach ($organizations as $org) {
            TenantContext::set($org);
            $this->info("Indexing organization: {$org->name} (ID {$org->id})");

            Contact::query()
                ->with(['contactEmails', 'company'])
                ->orderBy('id')
                ->chunk($chunk, function ($contacts) use (&$indexed): void {
                    foreach ($contacts as $contact) {
                        $content = $this->contactSummary($contact);
                        try {
                            $response = Embeddings::for([$content])->dimensions(1536)->generate();
                            $vector = $response->embeddings[0] ?? null;
                            if ($vector === null) {
                                continue;
                            }
                            EmbeddingDocument::withoutGlobalScope(OrganizationScope::class)->updateOrCreate(
                                [
                                    'organization_id' => $contact->organization_id,
                                    'document_type' => 'contact',
                                    'document_id' => (string) $contact->id,
                                ],
                                [
                                    'content' => $content,
                                    'embedding' => new Vector($vector),
                                ],
                            );
                            $indexed++;
                        } catch (Throwable $e) {
                            $this->warn("Contact {$contact->id}: {$e->getMessage()}");
                        }
                    }
                });
        }

        TenantContext::forget();
        $this->info("Indexed {$indexed} contact documents.");

        return self::SUCCESS;
    }

    private function contactSummary(Contact $c): string
    {
        $name = trim($c->first_name.' '.$c->last_name) ?: 'Unknown';
        $company = $c->company_name ?? $c->company?->name ?? '';
        $type = $c->type ?? '';
        $stage = $c->stage ?? '';
        $emails = $c->contactEmails->pluck('email')->take(3)->implode(', ');

        return "Contact: {$name}. Company: {$company}. Type: {$type}. Stage: {$stage}. Email: {$emails}.";
    }
}
