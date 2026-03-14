<?php

declare(strict_types=1);

namespace App\States\Sale;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class SaleState extends State
{
    final public static function config(): StateConfig
    {
        return parent::config()
            ->default(Active::class)
            ->allowTransition(Active::class, Unconditional::class)
            ->allowTransition(Unconditional::class, Settled::class)
            ->allowTransition([Active::class, Unconditional::class], Cancelled::class);
    }
}
