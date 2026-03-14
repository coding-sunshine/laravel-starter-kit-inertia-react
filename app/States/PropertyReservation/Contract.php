<?php

declare(strict_types=1);

namespace App\States\PropertyReservation;

final class Contract extends ReservationState
{
    public string $label = 'Contract';

    public string $color = 'yellow';
}
