<?php

declare(strict_types=1);

namespace App\Services\Railway\Contracts;

interface RrPdfTextExtractorContract
{
    public function extract(string $path): string;
}
