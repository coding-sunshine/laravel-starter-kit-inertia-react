export interface OverviewData {
    rrNumber: string;
    fnr: string;
    fromStation: string;
    toStation: string;
    distanceKm: string;
    commodity: string;
    totalWagons: number;
    totalWeight: string;
    freightTotal: string;
    rate: string;
    class: string;
}

export interface WagonRow {
    sequence: number;
    wagonNumber: string;
    wagonType: string;
    pccWeight: string;
    loadedWeight: string;
    permissibleWeight: string;
    overloadWeight: string;
    status: string;
}

export interface ChargeRow {
    chargeCode: string;
    chargeName: string;
    amount: string;
}

export interface PenaltyRow {
    penaltyCode: string;
    penaltyName: string;
    calculationType: string;
    amount: string;
    wagonReference?: string;
    overloadWeight?: string;
}
