/**
 * Parse Laravel JSON validation responses (classic bag, JSON:API errors[], top message).
 * Pass the set of form field `name`s you render so messages map to inputs.
 */

export function firstValidationMessage(raw: unknown): string | undefined {
    if (typeof raw === 'string') {
        const t = raw.trim();
        return t === '' ? undefined : t;
    }
    if (Array.isArray(raw) && raw.length > 0) {
        const s = String(raw[0]).trim();

        return s === '' ? undefined : s;
    }
    if (
        raw !== null &&
        typeof raw === 'object' &&
        !Array.isArray(raw) &&
        'message' in raw
    ) {
        return firstValidationMessage(
            (raw as { message: unknown }).message,
        );
    }

    return undefined;
}

export function normalizeLaravelTopMessage(
    body: Record<string, unknown>,
): string | null {
    const m = body.message;
    if (typeof m === 'string' && m.trim() !== '') {
        return m.trim();
    }
    if (Array.isArray(m) && m[0] !== undefined) {
        const s = String(m[0]).trim();

        return s === '' ? null : s;
    }

    return null;
}

/** Pull Laravel-style `errors` object from common JSON shapes. */
export function getLaravelErrorsBag(body: unknown): Record<string, unknown> | null {
    if (body === null || typeof body !== 'object') {
        return null;
    }
    const o = body as Record<string, unknown>;
    const direct = o.errors;
    if (
        direct !== null &&
        typeof direct === 'object' &&
        !Array.isArray(direct)
    ) {
        return direct as Record<string, unknown>;
    }
    const data = o.data;
    if (data !== null && typeof data === 'object' && !Array.isArray(data)) {
        const nested = (data as Record<string, unknown>).errors;
        if (
            nested !== null &&
            typeof nested === 'object' &&
            !Array.isArray(nested)
        ) {
            return nested as Record<string, unknown>;
        }
    }

    return null;
}

/**
 * JSON:API / RFC7807 style:
 * `{ "errors": [ { "detail": "…", "source": { "pointer": "/field" } } ] }`
 */
export function parseJsonApiStyleErrorsArray(
    body: unknown,
    knownFieldKeys: Set<string>,
): { fields: Record<string, string>; orphans: string[] } {
    const fields: Record<string, string> = {};
    const orphans: string[] = [];

    if (body === null || typeof body !== 'object') {
        return { fields, orphans };
    }
    const o = body as Record<string, unknown>;
    const errs = o.errors;
    if (!Array.isArray(errs)) {
        return { fields, orphans };
    }

    for (const item of errs) {
        if (item === null || typeof item !== 'object') {
            continue;
        }
        const row = item as Record<string, unknown>;
        const detailRaw = row.detail ?? row.title;
        const detail =
            typeof detailRaw === 'string'
                ? detailRaw.trim()
                : firstValidationMessage(detailRaw);
        if (!detail) {
            continue;
        }

        let pointer: string | undefined;
        const source = row.source;
        if (
            source !== null &&
            typeof source === 'object' &&
            !Array.isArray(source)
        ) {
            const p = (source as Record<string, unknown>).pointer;
            if (typeof p === 'string') {
                pointer = p;
            }
        }

        let mappedKey: string | null = null;
        if (pointer !== undefined && pointer !== '') {
            const segments = pointer.split('/').filter(Boolean);
            const last = segments[segments.length - 1];
            if (last !== undefined && knownFieldKeys.has(last)) {
                mappedKey = last;
            }
        }

        if (mappedKey !== null) {
            fields[mappedKey] = detail;
        } else {
            orphans.push(detail);
        }
    }

    return { fields, orphans };
}

/**
 * Map Laravel 422 JSON to field messages + optional banner (unknown fields / top message).
 */
export function parseLaravel422ResponseBody(
    body: unknown,
    knownFieldKeys: Set<string>,
): { fields: Record<string, string>; banner: string | null } {
    const fields: Record<string, string> = {};
    const orphanLines: string[] = [];

    const bag = getLaravelErrorsBag(body);
    if (bag) {
        for (const key of Object.keys(bag)) {
            const baseKey = key.includes('.')
                ? key.slice(0, key.indexOf('.'))
                : key;
            const msg = firstValidationMessage(bag[key]);
            if (msg === undefined) {
                continue;
            }
            if (knownFieldKeys.has(baseKey)) {
                fields[baseKey] = msg;
            } else {
                orphanLines.push(msg);
            }
        }
    }

    const jsonApi = parseJsonApiStyleErrorsArray(body, knownFieldKeys);
    for (const [k, v] of Object.entries(jsonApi.fields)) {
        if (v !== '') {
            fields[k] = v;
        }
    }
    orphanLines.push(...jsonApi.orphans);

    let banner: string | null = null;
    if (orphanLines.length > 0) {
        banner = [...new Set(orphanLines)].join('\n');
    }

    const hasField = Object.keys(fields).length > 0;
    if (!hasField && body !== null && typeof body === 'object') {
        const top = normalizeLaravelTopMessage(body as Record<string, unknown>);
        if (top !== null) {
            banner = banner !== null ? `${top}\n${banner}` : top;
        }
    }

    const trimmed =
        banner !== null && banner.trim() !== '' ? banner.trim() : null;

    return { fields, banner: trimmed };
}
