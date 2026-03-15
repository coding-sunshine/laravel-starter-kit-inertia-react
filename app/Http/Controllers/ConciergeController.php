<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Lot;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

/**
 * Property concierge — matches buyers to suitable properties via AI.
 * Uses Thesys C1 for generative UI responses with CRM context.
 */
final class ConciergeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('concierge/index');
    }

    public function match(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'message' => ['nullable', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        abort_if($user === null, 401);

        $message = $data['message'] ?? 'Find me suitable properties';
        $conversationId = $data['conversation_id'] ?? (string) Str::uuid();

        // Build CRM context for the AI
        $context = $this->buildCrmContext($message);

        // Try Thesys C1 first for rich UI responses
        $thesysKey = config('services.thesys.api_key') ?: env('THESYS_API_KEY');

        if ($thesysKey) {
            try {
                $reply = $this->queryThesys($thesysKey, $message, $context);

                return response()->json([
                    'success' => true,
                    'reply' => $reply,
                    'html' => true,
                    'conversation_id' => $conversationId,
                ]);
            } catch (Throwable $e) {
                Log::warning('Thesys C1 failed, falling back', ['error' => $e->getMessage()]);
            }
        }

        // Fallback: use Prism or return a simple DB-based response
        try {
            $reply = $this->queryDatabase($message);

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'conversation_id' => $conversationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'reply' => 'Unable to process your request. Please try again.',
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    private function buildCrmContext(string $query): string
    {
        $stats = [
            'total_projects' => Project::withoutGlobalScopes()->count(),
            'total_contacts' => Contact::withoutGlobalScopes()->count(),
            'total_lots' => Lot::withoutGlobalScopes()->count(),
            'available_lots' => Lot::withoutGlobalScopes()->where('title_status', 'available')->count(),
        ];

        // Get sample projects matching the query keywords
        $projectQuery = Project::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('is_archived', false);

        // Extract location keywords
        $keywords = preg_split('/[\s,]+/', $query);
        $locationMatches = [];

        foreach ($keywords as $word) {
            if (mb_strlen($word) > 2 && ! in_array(mb_strtolower($word), ['find', 'show', 'properties', 'under', 'over', 'with', 'and', 'the', 'for', 'lots', 'available'])) {
                $matches = Project::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($word) {
                        $q->where('suburb', 'ILIKE', "%{$word}%")
                            ->orWhere('state', 'ILIKE', "%{$word}%")
                            ->orWhere('title', 'ILIKE', "%{$word}%");
                    })
                    ->limit(10)
                    ->get(['id', 'title', 'suburb', 'state', 'min_price', 'max_price', 'total_lots', 'stage', 'is_hot_property']);

                foreach ($matches as $m) {
                    $locationMatches[$m->id] = $m;
                }
            }
        }

        // Extract price from query
        if (preg_match('/\$?([\d,]+)k/i', $query, $m)) {
            $maxPrice = (float) str_replace(',', '', $m[1]) * 1000;
            $projectQuery->where('min_price', '<=', $maxPrice);
        } elseif (preg_match('/\$?([\d,]+(?:\.\d+)?)\s*(?:million|m)/i', $query, $m)) {
            $maxPrice = (float) str_replace(',', '', $m[1]) * 1_000_000;
            $projectQuery->where('min_price', '<=', $maxPrice);
        }

        $sampleProjects = count($locationMatches) > 0
            ? array_values($locationMatches)
            : $projectQuery->orderByDesc('updated_at')->limit(10)->get(['id', 'title', 'suburb', 'state', 'min_price', 'max_price', 'total_lots', 'stage', 'is_hot_property'])->all();

        $projectsJson = json_encode(array_map(fn ($p) => [
            'title' => $p->title,
            'suburb' => $p->suburb,
            'state' => $p->state,
            'from_price' => $p->min_price ? '$'.number_format((float) $p->min_price) : null,
            'to_price' => $p->max_price ? '$'.number_format((float) $p->max_price) : null,
            'lots' => $p->total_lots,
            'stage' => $p->stage,
            'hot' => $p->is_hot_property,
        ], $sampleProjects), JSON_PRETTY_PRINT);

        return "You are the Fusion CRM AI Assistant for a real estate property investment agency in Australia.

CRM Stats: {$stats['total_projects']} projects, {$stats['total_contacts']} contacts, {$stats['total_lots']} lots ({$stats['available_lots']} available).

Matching properties for the user's query:
{$projectsJson}

Respond helpfully with property recommendations. Format prices in AUD. Be concise and actionable.";
    }

    private function queryThesys(string $apiKey, string $message, string $context): string
    {
        $response = Http::withToken($apiKey)
            ->timeout(45)
            ->post('https://api.thesys.dev/v1/embed/chat/completions', [
                'model' => env('THESYS_MODEL', config('services.thesys.model', 'c1-nightly')),
                'messages' => [
                    ['role' => 'system', 'content' => $context],
                    ['role' => 'user', 'content' => $message],
                ],
                'stream' => false,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Thesys API error: '.$response->status().' '.$response->body());
        }

        $data = $response->json();

        $content = $data['choices'][0]['message']['content']
            ?? $data['content']
            ?? 'No response generated.';

        // Thesys returns content wrapped in <content thesys="true">...</content>
        // Extract the JSON component tree and convert to readable text
        if (str_contains($content, 'thesys="true"')) {
            $content = $this->parseThesysContent($content);
        }

        return $content;
    }

    /**
     * Parse Thesys C1 component JSON into readable markdown.
     */
    private function parseThesysContent(string $raw): string
    {
        // Extract JSON from <content thesys="true">...</content>
        $json = preg_replace('/<content[^>]*>(.*)<\/content>/s', '$1', $raw);
        $json = html_entity_decode($json, ENT_QUOTES, 'UTF-8');
        $decoded = json_decode($json, true);

        if (! $decoded) {
            // Fallback: strip tags and return plain text
            return strip_tags($raw);
        }

        return $this->renderComponent($decoded['component'] ?? $decoded);
    }

    private function renderComponent(array $component): string
    {
        $type = $component['component'] ?? '';
        $props = $component['props'] ?? [];
        $lines = [];

        // Handle title/subtitle from Header
        if ($type === 'Header') {
            if (! empty($props['title'])) {
                $lines[] = '**'.$props['title'].'**';
            }
            if (! empty($props['subtitle'])) {
                $lines[] = $props['subtitle'];
            }
            $lines[] = '';
        }

        // Handle text content
        if ($type === 'TextContent' && ! empty($props['textMarkdown'])) {
            $lines[] = $props['textMarkdown'];
            $lines[] = '';
        }

        // Handle tables
        if ($type === 'Table') {
            $headers = $props['headers'] ?? [];
            $rows = $props['rows'] ?? [];

            if (! empty($headers)) {
                $lines[] = '| '.implode(' | ', $headers).' |';
                $lines[] = '| '.implode(' | ', array_fill(0, count($headers), '---')).' |';
                foreach ($rows as $row) {
                    $cells = is_array($row) ? $row : [$row];
                    $lines[] = '| '.implode(' | ', $cells).' |';
                }
                $lines[] = '';
            }
        }

        // Handle list items
        if ($type === 'BulletedList' || $type === 'NumberedList') {
            $items = $props['items'] ?? [];
            foreach ($items as $i => $item) {
                $prefix = $type === 'NumberedList' ? ($i + 1).'.' : '-';
                $text = is_array($item) ? ($item['text'] ?? json_encode($item)) : $item;
                $lines[] = $prefix.' '.$text;
            }
            $lines[] = '';
        }

        // Recursively render children
        $children = $props['children'] ?? [];
        if (is_array($children)) {
            foreach ($children as $child) {
                if (is_array($child)) {
                    $lines[] = $this->renderComponent($child);
                }
            }
        }

        return implode("\n", array_filter($lines, fn ($l) => $l !== null));
    }

    /**
     * Simple DB-based fallback when no AI is available.
     */
    private function queryDatabase(string $message): string
    {
        $projects = Project::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('is_archived', false)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['title', 'suburb', 'state', 'min_price', 'total_lots', 'stage']);

        if ($projects->isEmpty()) {
            return 'No projects found matching your criteria.';
        }

        $lines = ["Here are some recent properties:\n"];
        foreach ($projects as $p) {
            $price = $p->min_price ? '$'.number_format((float) $p->min_price) : 'Price TBC';
            $location = implode(', ', array_filter([$p->suburb, $p->state]));
            $lines[] = "- **{$p->title}** ({$location}) — {$price}, {$p->total_lots} lots, {$p->stage}";
        }

        return implode("\n", $lines);
    }
}
