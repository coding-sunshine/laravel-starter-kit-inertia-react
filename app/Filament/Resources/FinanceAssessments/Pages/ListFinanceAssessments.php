<?php

declare(strict_types=1);

namespace App\Filament\Resources\FinanceAssessments\Pages;

use App\Filament\Resources\FinanceAssessments\FinanceAssessmentResource;
use Filament\Resources\Pages\ListRecords;

final class ListFinanceAssessments extends ListRecords
{
    protected static string $resource = FinanceAssessmentResource::class;
}
