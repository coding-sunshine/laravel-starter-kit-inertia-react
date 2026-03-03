<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questionnaires\Pages;

use App\Filament\Resources\Questionnaires\QuestionnaireResource;
use Filament\Resources\Pages\ListRecords;

final class ListQuestionnaires extends ListRecords
{
    protected static string $resource = QuestionnaireResource::class;
}
