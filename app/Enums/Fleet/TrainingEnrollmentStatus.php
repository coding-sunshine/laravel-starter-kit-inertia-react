<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum TrainingEnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Confirmed = 'confirmed';
    case Attended = 'attended';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}
