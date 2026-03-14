<?php

declare(strict_types=1);

namespace App\States\Sale;

final class Settled extends SaleState
{
    public string $label = 'Settled';

    public string $color = 'green';
}
