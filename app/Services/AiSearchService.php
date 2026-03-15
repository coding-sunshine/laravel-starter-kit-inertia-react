<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Throwable;

/**
 * Enhanced search combining Scout full-text search with pgvector similarity and Cohere reranking.
 */
final readonly class AiSearchService
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    /**
     * Search contacts with hybrid text + vector search and optional reranking.
     *
     * @return Collection<int, Contact>
     */
    public function searchContacts(string $query, int $limit = 20, bool $rerank = true): Collection
    {
        // 1. Scout full-text search
        $textResults = Contact::search($query)
            ->take($limit)
            ->get();

        // 2. Vector similarity search
        $vectorResults = $this->vectorSearch($query, $limit);

        // 3. Merge and deduplicate
        $merged = $textResults->merge($vectorResults)->unique('id')->take($limit * 2);

        // 4. Rerank if AI is available
        if ($rerank && $merged->count() > 1) {
            return $this->rerankResults($query, $merged, $limit);
        }

        return $merged->take($limit);
    }

    /**
     * Find contacts similar to a given contact using vector proximity.
     *
     * @return Collection<int, Contact>
     */
    public function findSimilar(Contact $contact, int $limit = 5): Collection
    {
        $embedding = DB::table('contact_embeddings')
            ->where('contact_id', $contact->id)
            ->where('type', 'full')
            ->value('embedding');

        if ($embedding === null) {
            return collect();
        }

        $results = DB::select(
            "SELECT ce.contact_id, ce.embedding <=> ? AS distance
             FROM contact_embeddings ce
             WHERE ce.contact_id != ? AND ce.type = 'full' AND ce.embedding IS NOT NULL
             ORDER BY distance ASC
             LIMIT ?",
            [$embedding, $contact->id, $limit]
        );

        $ids = array_map(fn ($row) => $row->contact_id, $results);

        if ($ids === []) {
            return collect();
        }

        return Contact::query()->whereIn('id', $ids)->get()
            ->sortBy(fn (Contact $c) => array_search($c->id, $ids, true));
    }

    /**
     * Vector similarity search using pgvector.
     *
     * @return Collection<int, Contact>
     */
    private function vectorSearch(string $query, int $limit): Collection
    {
        try {
            $embeddingResponse = Prism::embeddings()
                ->using(Provider::OpenAI, 'text-embedding-3-small')
                ->fromInput($query)
                ->asEmbeddings();

            $vector = $embeddingResponse->embeddings[0] ?? null;

            if ($vector === null) {
                return collect();
            }

            $vectorString = '['.implode(',', $vector).']';

            $results = DB::select(
                "SELECT ce.contact_id, ce.embedding <=> ?::vector AS distance
                 FROM contact_embeddings ce
                 WHERE ce.type = 'full' AND ce.embedding IS NOT NULL
                 ORDER BY distance ASC
                 LIMIT ?",
                [$vectorString, $limit]
            );

            $ids = array_map(fn ($row) => $row->contact_id, $results);

            if ($ids === []) {
                return collect();
            }

            return Contact::query()->whereIn('id', $ids)->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Rerank results using Cohere reranker via Prism.
     *
     * @param  Collection<int, Contact>  $results
     * @return Collection<int, Contact>
     */
    private function rerankResults(string $query, Collection $results, int $limit): Collection
    {
        try {
            // Build documents for reranking
            $documents = $results->map(fn (Contact $c): string => implode(' | ', array_filter([
                $c->first_name.' '.($c->last_name ?? ''),
                $c->type,
                $c->company_name,
                $c->stage,
                $c->contact_origin,
            ])))->values()->all();

            // Use Prism text to rerank (ask AI to order results)
            $response = $this->prism->text()
                ->withSystemPrompt('You are a search result ranker. Given a query and candidate results, return a comma-separated list of the result numbers (1-indexed) in order of relevance. Only return numbers, nothing else.')
                ->withPrompt("Query: {$query}\n\nResults:\n".implode("\n", array_map(
                    fn (int $i, string $doc) => ($i + 1).'. '.$doc,
                    array_keys($documents),
                    $documents
                )))
                ->generate();

            $orderedIndices = array_filter(
                array_map('intval', explode(',', $response->text)),
                fn (int $i) => $i > 0 && $i <= $results->count()
            );

            if ($orderedIndices === []) {
                return $results->take($limit);
            }

            $resultValues = $results->values();
            $reranked = collect();

            foreach ($orderedIndices as $index) {
                $contact = $resultValues[$index - 1] ?? null;
                if ($contact !== null) {
                    $reranked->push($contact);
                }
            }

            // Append any results not in the reranked list
            $rerankedIds = $reranked->pluck('id')->all();
            $remaining = $results->reject(fn (Contact $c) => in_array($c->id, $rerankedIds, true));

            return $reranked->merge($remaining)->take($limit);
        } catch (Throwable) {
            return $results->take($limit);
        }
    }
}
