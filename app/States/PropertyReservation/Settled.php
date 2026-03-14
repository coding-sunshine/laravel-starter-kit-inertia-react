<?php

declare(strict_types=1);

namespace App\States\PropertyReservation;

final class Settled extends ReservationState
{
    public string $label = 'Settled';

    public string $color = 'green';
}
