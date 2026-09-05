<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Models\Indent;
use App\Models\Rake;
use Illuminate\Support\Facades\DB;

final class SearchForCommandPaletteAction
{
    private const MIN_QUERY_LENGTH = 2;

    private const PER_CATEGORY_LIMIT = 10;

    public function handle(string $query): CommandPaletteResults
    {
        $q = mb_trim($query);

        if (mb_strlen($q) < self::MIN_QUERY_LENGTH) {
            return new CommandPaletteResults();
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
        $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $rakes = Rake::query()
            ->select(['rakes.id', 'rakes.rake_number', 'sidings.name as siding_name', 'rakes.state'])
            ->leftJoin('sidings', 'sidings.id', '=', 'rakes.siding_id')
            ->where('rakes.rake_number', $likeOp, $like)
            ->orderByDesc('rakes.id')
            ->limit(self::PER_CATEGORY_LIMIT)
            ->get()
            ->map(fn ($r): array => [
                'id' => (int) $r->id,
                'rake_number' => (string) $r->rake_number,
                'siding_name' => $r->siding_name,
                'status' => $r->state,
            ])
            ->values()
            ->all();

        $indents = Indent::query()
            ->select(['id', 'indent_number', 'e_demand_reference_id'])
            ->where(function ($builder) use ($likeOp, $like): void {
                $builder->where('indent_number', $likeOp, $like)
                    ->orWhere('e_demand_reference_id', $likeOp, $like);
            })
            ->orderByDesc('id')
            ->limit(self::PER_CATEGORY_LIMIT)
            ->get()
            ->map(fn ($i): array => [
                'id' => (int) $i->id,
                'indent_number' => (string) $i->indent_number,
                'e_demand_number' => $i->e_demand_reference_id,
            ])
            ->values()
            ->all();

        $rrs = DB::table('rr_documents')
            ->select(['id', 'rr_number', 'rake_id'])
            ->where('rr_number', $likeOp, $like)
            ->orderByDesc('id')
            ->limit(self::PER_CATEGORY_LIMIT)
            ->get()
            ->map(fn ($r): array => [
                'id' => (int) $r->id,
                'rr_number' => (string) $r->rr_number,
                'rake_id' => isset($r->rake_id) ? (int) $r->rake_id : null,
            ])
            ->values()
            ->all();

        return new CommandPaletteResults(rakes: $rakes, indents: $indents, rrs: $rrs);
    }
}
