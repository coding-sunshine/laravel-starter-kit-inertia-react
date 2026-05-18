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

    /*
    |--------------------------------------------------------------------------
    | Official wagon carrying capacity (CC) table
    |--------------------------------------------------------------------------
    |
    | Source: the railway's "CC WEIGHT" sheet supplied by the client (not
    | present anywhere in Loadrite data). This is the AUTHORITATIVE CC — the
    | fleet's own pcc_weight_mt column has errors (e.g. it carried 79 MT for
    | BOXNS where the real CC is 70.70). Keyed by full wagon type. cc + tare
    | in MT. Note: real-world weights can shift slightly in monsoon season.
    |
    */
    'wagon_cc' => [
        'BOXNM1' => ['cc' => 68.00, 'tare' => 22.53],
        'BOXNHSM1' => ['cc' => 68.00, 'tare' => 22.53],
        'BOXNM2' => ['cc' => 68.00, 'tare' => 22.53],
        'BOXNHSM2' => ['cc' => 68.00, 'tare' => 22.53],
        'BOXNCR' => ['cc' => 68.00, 'tare' => 22.48],
        'BOXN' => ['cc' => 68.00, 'tare' => 22.48],
        'BOXNHA' => ['cc' => 67.00, 'tare' => 23.17],
        'BOXNHAM' => ['cc' => 67.00, 'tare' => 23.17],
        'BOXNEL' => ['cc' => 67.00, 'tare' => 23.10],
        'BOXNR' => ['cc' => 69.40, 'tare' => 21.20],
        'BOXNRHS' => ['cc' => 69.40, 'tare' => 21.20],
        'BOXNRM2' => ['cc' => 69.40, 'tare' => 21.20],
        'BOXNHL' => ['cc' => 70.00, 'tare' => 20.60],
        'BOXNHL25T' => ['cc' => 70.00, 'tare' => 20.60],
        'BOXNHL2D' => ['cc' => 70.00, 'tare' => 20.60],
        'BOXNLW' => ['cc' => 70.00, 'tare' => 20.60],
        'BOXNG' => ['cc' => 59.00, 'tare' => 21.06],
        'BOXNS' => ['cc' => 70.70, 'tare' => 19.85],
        'BOBR' => ['cc' => 62.20, 'tare' => 26.40],
        'BOBRM1' => ['cc' => 62.20, 'tare' => 26.40],
        'BOBRN' => ['cc' => 65.00, 'tare' => 25.61],
        'BOBRNM1' => ['cc' => 65.00, 'tare' => 25.61],
        'BOBRNHSM1' => ['cc' => 65.00, 'tare' => 25.61],
        'BOBRNHSM2' => ['cc' => 65.00, 'tare' => 25.61],
        'BOBRNEL' => ['cc' => 65.00, 'tare' => 25.61],
        'BOBRNHS' => ['cc' => 65.00, 'tare' => 25.61],
    ],

    /*
    |--------------------------------------------------------------------------
    | Loadrite type-abbreviation -> full wagon type
    |--------------------------------------------------------------------------
    |
    | Operators key a short type code into the event UserData. This maps each
    | observed code to its full wagon type so the official CC table can be
    | applied. Ambiguous codes (HSM1/HSM2/NM1 could be BOXN-68 or BOBRN-65)
    | default to the BOBRN variant — the dominant fleet type — but are
    | overridden whenever the wagon number resolves to a real fleet wagon.
    |
    */
    'type_abbreviations' => [
        'HL' => 'BOXNHL',
        'HL2D' => 'BOXNHL2D',
        'HL25T' => 'BOXNHL25T',
        'HL25TD' => 'BOXNHL25T',
        'LW' => 'BOXNLW',
        'NLW' => 'BOXNLW',
        'NS' => 'BOXNS',
        'NR' => 'BOXNR',
        'RHS' => 'BOXNRHS',
        'RM2' => 'BOXNRM2',
        'HA' => 'BOXNHA',
        'HAM' => 'BOXNHAM',
        'EL' => 'BOXNEL',
        'CR' => 'BOXNCR',
        'G' => 'BOXNG',
        'M1' => 'BOXNM1',
        'M2' => 'BOXNM2',
        'HSM1' => 'BOBRNHSM1',
        'HSM2' => 'BOBRNHSM2',
        'SM1' => 'BOBRNHSM1',
        'SM2' => 'BOBRNHSM2',
        'NM1' => 'BOBRNM1',
        'NM2' => 'BOBRNM1',
        'RM1' => 'BOBRM1',
    ],
];
