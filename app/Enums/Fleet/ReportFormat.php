<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ReportFormat: string
{
    case Pdf = 'pdf';
    case Excel = 'excel';
    case Csv = 'csv';
    case Json = 'json';
}
