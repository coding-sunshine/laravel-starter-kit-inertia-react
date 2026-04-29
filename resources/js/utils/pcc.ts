export const PENALTY_RATE_PER_EXCESS_MT = 60; // ₹ per excess MT (Indian Railways)

export type PccStatus = 'over' | 'near' | 'ok' | 'empty';

export interface WagonPccState {
    wagonId: number;
    wagonNumber: string;
    pccMt: number;
    loadedMt: number;
    pct: number;
    status: PccStatus;
    excessMt: number;
    penaltyRs: number;
}

export function parseMt(value: string | number | null | undefined): number {
    const n = parseFloat(String(value ?? '0'));
    return isNaN(n) ? 0 : n;
}

export function calcPccPct(loadedMt: number, pccMt: number): number {
    if (pccMt <= 0) return 0;
    return (loadedMt / pccMt) * 100;
}

export function calcStatus(loadedMt: number, pccMt: number): PccStatus {
    if (loadedMt <= 0) return 'empty';
    const pct = calcPccPct(loadedMt, pccMt);
    if (pct >= 100) return 'over';
    if (pct >= 90) return 'near';
    return 'ok';
}

export function calcPenaltyRs(loadedMt: number, pccMt: number): number {
    if (loadedMt <= pccMt || pccMt <= 0) return 0;
    const excessMt = loadedMt - pccMt;
    return Math.round(excessMt * PENALTY_RATE_PER_EXCESS_MT * 100) / 100;
}

export function buildWagonPccState(
    wagonId: number,
    wagonNumber: string,
    pccMtRaw: string | number | null | undefined,
    loadedMtRaw: string | number | null | undefined,
): WagonPccState {
    const pccMt = parseMt(pccMtRaw);
    const loadedMt = parseMt(loadedMtRaw);
    const pct = calcPccPct(loadedMt, pccMt);
    const status = calcStatus(loadedMt, pccMt);
    const excessMt = Math.max(0, loadedMt - pccMt);
    const penaltyRs = calcPenaltyRs(loadedMt, pccMt);
    return { wagonId, wagonNumber, pccMt, loadedMt, pct, status, excessMt, penaltyRs };
}

export function summarisePccStates(states: WagonPccState[]): {
    ok: number;
    near: number;
    over: number;
    empty: number;
    totalPenaltyRs: number;
    totalExcessMt: number;
} {
    return states.reduce(
        (acc, s) => ({
            ok:             acc.ok + (s.status === 'ok' ? 1 : 0),
            near:           acc.near + (s.status === 'near' ? 1 : 0),
            over:           acc.over + (s.status === 'over' ? 1 : 0),
            empty:          acc.empty + (s.status === 'empty' ? 1 : 0),
            totalPenaltyRs: acc.totalPenaltyRs + s.penaltyRs,
            totalExcessMt:  acc.totalExcessMt + s.excessMt,
        }),
        { ok: 0, near: 0, over: 0, empty: 0, totalPenaltyRs: 0, totalExcessMt: 0 },
    );
}
