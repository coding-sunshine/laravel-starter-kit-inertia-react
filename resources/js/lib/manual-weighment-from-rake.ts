/**
 * Default manual rake weighment fields from the rake record (loading siding → from, destination → to).
 */
export type RakeLikeForManualWeighment = {
    siding?: { name?: string | null; code?: string | null } | null;
    destination?: string | null;
    destination_code?: string | null;
    priority_number?: number | string | null;
};

export function manualWeighmentFieldsFromRake(rake: RakeLikeForManualWeighment): {
    from_station: string;
    to_station: string;
    priority_number: string;
} {
    const code = rake.siding?.code?.trim() ?? '';
    const name = rake.siding?.name?.trim() ?? '';
    const from_station =
        code !== '' && name !== '' ? `${code} — ${name}` : name !== '' ? name : code;

    const dest = typeof rake.destination === 'string' ? rake.destination.trim() : '';
    const destCode = typeof rake.destination_code === 'string' ? rake.destination_code.trim() : '';
    const to_station = dest !== '' ? dest : destCode;

    const prio = rake.priority_number;
    const priority_number =
        prio === null || prio === undefined || prio === '' ? '' : String(prio);

    return { from_station, to_station, priority_number };
}
