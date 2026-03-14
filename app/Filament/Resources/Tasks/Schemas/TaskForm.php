<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Schemas\Schema;

final class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
