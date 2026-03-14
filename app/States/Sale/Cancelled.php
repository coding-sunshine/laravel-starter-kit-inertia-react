<?php

declare(strict_types=1);

namespace App\States\Sale;

final class Cancelled extends SaleState
{
    public string $label = 'Cancelled';

    public string $color = 'red';
}
