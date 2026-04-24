/**
 * Default manual rake weighment fields from the rake record (loading siding → from, destination → to).
 */
export type RakeLikeForManualWeighment = {
    siding?: { name?: string | null; code?: string | null } | null;
    destination?: string | null;
    destination_code?: string | null;
};

export function manualWeighmentFieldsFromRake(rake: RakeLikeForManualWeighment): {
    from_station: string;
    to_station: string;
} {
    const code = rake.siding?.code?.trim() ?? '';
    const name = rake.siding?.name?.trim() ?? '';
    const from_station =
        code !== '' && name !== '' ? `${code} — ${name}` : name !== '' ? name : code;

    const dest = typeof rake.destination === 'string' ? rake.destination.trim() : '';
    const destCode = typeof rake.destination_code === 'string' ? rake.destination_code.trim() : '';
    const to_station = dest !== '' ? dest : destCode;

    return { from_station, to_station };
}
