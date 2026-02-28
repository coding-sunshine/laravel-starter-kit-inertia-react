<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum TachographDownloadStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
    case Archived = 'archived';
}
