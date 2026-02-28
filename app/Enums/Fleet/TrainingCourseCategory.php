<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum TrainingCourseCategory: string
{
    case Safety = 'safety';
    case Compliance = 'compliance';
    case Skills = 'skills';
    case Induction = 'induction';
    case Refresher = 'refresher';
}
