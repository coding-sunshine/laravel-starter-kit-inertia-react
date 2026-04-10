/**
 * Decodes `return_to` query values for same-origin navigation only (open-redirect safe).
 */
export function parseSafeReturnTo(raw: string | null): string | null {
    if (raw == null || raw === '') {
        return null;
    }
    let decoded: string;
    try {
        decoded = decodeURIComponent(raw);
    } catch {
        return null;
    }
    if (!decoded.startsWith('/') || decoded.startsWith('//')) {
        return null;
    }
    if (decoded.includes('://')) {
        return null;
    }
    return decoded;
}

/**
 * Appends a `return_to` query param (encoded internal path) for rake show "back" navigation.
 */
export function withReturnTo(path: string, returnPath: string): string {
    const sep = path.includes('?') ? '&' : '?';
    return `${path}${sep}return_to=${encodeURIComponent(returnPath)}`;
}
