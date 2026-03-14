<?php

declare(strict_types=1);

namespace App\States\PropertyReservation;

final class Unconditional extends ReservationState
{
    public string $label = 'Unconditional';

    public string $color = 'purple';
}
