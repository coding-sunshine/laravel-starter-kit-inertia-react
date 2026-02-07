<?php

declare(strict_types=1);

use App\Models\ModelFlag;

return [
    /*
     * The model used as the flag model.
     * Uses custom ModelFlag to avoid table name conflict with other packages.
     */
    'flag_model' => ModelFlag::class,
];
