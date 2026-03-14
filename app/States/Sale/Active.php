<?php

declare(strict_types=1);

namespace App\States\Sale;

final class Active extends SaleState
{
    public string $label = 'Active';

    public string $color = 'blue';
}
