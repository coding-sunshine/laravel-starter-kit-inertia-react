<?php

declare(strict_types=1);

namespace App\States\PropertyReservation;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ReservationState extends State
{
    final public static function config(): StateConfig
    {
        return parent::config()
            ->default(Enquiry::class)
            ->allowTransition(Enquiry::class, Qualified::class)
            ->allowTransition(Qualified::class, Reservation::class)
            ->allowTransition(Reservation::class, Contract::class)
            ->allowTransition(Contract::class, Unconditional::class)
            ->allowTransition(Unconditional::class, Settled::class);
    }
}
