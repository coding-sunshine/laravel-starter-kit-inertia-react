<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DashcamClipStatus: string
{
    case Available = 'available';
    case Archived = 'archived';
    case Deleted = 'deleted';
    case Processing = 'processing';
}
