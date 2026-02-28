<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum TrainingDeliveryMethod: string
{
    case Classroom = 'classroom';
    case Online = 'online';
    case Practical = 'practical';
    case Blended = 'blended';
}
