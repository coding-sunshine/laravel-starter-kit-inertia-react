<?php

declare(strict_types=1);

namespace App\Actions\Search;

/**
 * @phpstan-type RakeHit array{id: int, rake_number: string, siding_name: string|null, status: string|null}
 * @phpstan-type IndentHit array{id: int, indent_number: string, e_demand_number: string|null}
 * @phpstan-type RrHit array{id: int, rr_number: string, rake_id: int|null}
 */
final readonly class CommandPaletteResults
{
    /**
     * @param  list<RakeHit>  $rakes
     * @param  list<IndentHit>  $indents
     * @param  list<RrHit>  $rrs
     */
    public function __construct(
        public array $rakes = [],
        public array $indents = [],
        public array $rrs = [],
    ) {}
}
