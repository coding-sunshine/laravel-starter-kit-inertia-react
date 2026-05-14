/**
 * RR wagon table display: derive overload from loaded/permissible; store column can be wrong
 * (e.g. `.85` parsed as `85`). Overload numeric display always uses two fractional digits (toFixed(2)).
 */

const MT_TOLERANCE = 0.005;

export function parseFiniteMt(value: unknown): number | null {
    if (value === null || value === undefined) {
        return null;
    }
    if (typeof value === 'string' && value.trim() === '') {
        return null;
    }
    const n = Number(value);

    return Number.isFinite(n) ? n : null;
}

export function derivedOverloadMt(
    loadedMt: number | null,
    permissibleMt: number | null,
): number | null {
    if (loadedMt === null || permissibleMt === null) {
        return null;
    }

    const raw = loadedMt - permissibleMt;

    return raw <= 0 ? 0 : raw;
}

export function formatOverloadDisplayTwoDecimals(value: number): string {
    return Math.max(0, value).toFixed(2);
}

export function rrWagonStatusFromWeights(opts: {
    overloadMt: number | null;
    loadedMt: number | null;
    pccMt: number | null;
}): string {
    const { overloadMt: overload, loadedMt: loaded, pccMt: pcc } = opts;

    if (overload !== null && overload > MT_TOLERANCE) {
        return 'Overload';
    }

    if (loaded !== null && pcc !== null && loaded + MT_TOLERANCE < pcc) {
        return 'Underload';
    }

    return 'Loaded';
}

export function wagonRowOverloadDisplay(input: {
    pccRaw: unknown;
    loadedRaw: unknown;
    permissibleRaw: unknown;
    storedOverloadRaw: unknown;
}): { overloadWeight: string; status: string } {
    const pccMt = parseFiniteMt(input.pccRaw);
    const loadedMt = parseFiniteMt(input.loadedRaw);
    const permissibleMt = parseFiniteMt(input.permissibleRaw);
    const derived = derivedOverloadMt(loadedMt, permissibleMt);
    const storedOverloadMt = parseFiniteMt(input.storedOverloadRaw);

    let overloadWeight: string;
    let overloadForStatus: number | null;

    if (derived !== null) {
        overloadForStatus = derived;
        overloadWeight = formatOverloadDisplayTwoDecimals(derived);
    } else if (storedOverloadMt !== null) {
        overloadForStatus = Math.max(0, storedOverloadMt);
        overloadWeight = formatOverloadDisplayTwoDecimals(storedOverloadMt);
    } else if (
        input.storedOverloadRaw != null &&
        `${input.storedOverloadRaw}`.trim() !== ''
    ) {
        overloadWeight = String(input.storedOverloadRaw);
        overloadForStatus = null;
    } else {
        overloadWeight = '-';
        overloadForStatus = null;
    }

    const status = rrWagonStatusFromWeights({
        overloadMt: overloadForStatus,
        loadedMt,
        pccMt,
    });

    return { overloadWeight, status };
}
