<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Per-scale UserData field layout
    |--------------------------------------------------------------------------
    |
    | Loadrite events carry UserData1-4. Operators key the rake number, wagon
    | number and wagon type into these fields, but the slot each value lands in
    | differs per scale (verified against live Dumka data 2026-05-16):
    |
    |   Scales 11/13/16 : UD1=rake, UD2=wagon, UD3=type, Operator field used
    |   Scale 17        : UD1=operator, UD2=rake, UD3=wagon, UD4=type
    |
    | Keys are scale ids (string). Values name the UserData field holding each
    | datum. A scale not listed here falls back to the heuristic in
    | LoadriteUserDataParser (rake = small int, wagon = larger int, type =
    | alphabetic token).
    |
    */
    'scale_layouts' => [
        '11' => ['rake' => 'UserData1', 'wagon' => 'UserData2', 'type' => 'UserData3'],
        '13' => ['rake' => 'UserData1', 'wagon' => 'UserData2', 'type' => 'UserData3'],
        '16' => ['rake' => 'UserData1', 'wagon' => 'UserData2', 'type' => 'UserData3'],
        '17' => ['rake' => 'UserData2', 'wagon' => 'UserData3', 'type' => 'UserData4', 'operator' => 'UserData1'],
    ],
];
