<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum FaultDetermination: string
{
    case OurFault = 'our_fault';
    case ThirdPartyFault = 'third_party_fault';
    case NoFault = 'no_fault';
    case Disputed = 'disputed';
    case Unknown = 'unknown';
}
