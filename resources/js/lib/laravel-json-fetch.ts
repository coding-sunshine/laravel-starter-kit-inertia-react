/**
 * Read Laravel XSRF-TOKEN cookie for VerifyCsrfToken.
 */
export function getXsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return match?.[1] != null ? decodeURIComponent(match[1]) : '';
}

export type JsonFetchOptions = Omit<RequestInit, 'headers'> & {
    headers?: Record<string, string>;
};

export async function laravelJsonFetch<T>(url: string, init: JsonFetchOptions = {}): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...init.headers,
    };
    const token = getXsrfToken();
    if (token !== '') {
        headers['X-XSRF-TOKEN'] = token;
    }
    const response = await fetch(url, {
        ...init,
        credentials: 'same-origin',
        headers,
    });

    const contentType = response.headers.get('content-type');
    const isJson = contentType?.includes('application/json');
    const body = isJson ? await response.json() : await response.text();

    if (!response.ok) {
        const message =
            typeof body === 'object' && body !== null && 'message' in body
                ? String((body as { message: unknown }).message)
                : typeof body === 'string'
                  ? body
                  : `Request failed (${response.status})`;
        throw new JsonFetchError(message, response.status, body);
    }

    return body as T;
}

export class JsonFetchError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly body: unknown,
    ) {
        super(message);
        this.name = 'JsonFetchError';
    }
}

/** Multipart POST expecting JSON (200 with body, or 4xx/5xx with JSON errors). */
export async function postFormDataExpectJson<T>(
    url: string,
    formData: FormData,
): Promise<
    | { ok: true; data: T }
    | { ok: false; status: number; body: unknown }
> {
    const response = await fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getXsrfToken(),
        },
    });

    const body: unknown = await response.json().catch(() => ({}));

    if (response.ok) {
        return { ok: true, data: body as T };
    }

    return { ok: false, status: response.status, body };
}

/** Multipart POST to `railway-receipts/import` with JSON response (hub / rake page parity). */
export async function postRailwayReceiptImport(
    rakeId: number,
    file: File,
    diverrtDestinationId: number | null,
): Promise<{ rr_document: { id: number; rr_number?: string; diverrt_destination_id?: number | null }; rr_hub: unknown }> {
    const formData = new FormData();
    formData.append('pdf', file);
    formData.append('rake_id', String(rakeId));
    if (diverrtDestinationId !== null) {
        formData.append('diverrt_destination_id', String(diverrtDestinationId));
    }

    const response = await fetch('/railway-receipts/import', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getXsrfToken(),
        },
    });

    const body: unknown = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message =
            typeof body === 'object' && body !== null && 'message' in body
                ? String((body as { message: unknown }).message)
                : `Upload failed (${response.status})`;
        throw new JsonFetchError(message, response.status, body);
    }

    return body as { rr_document: { id: number; rr_number?: string; diverrt_destination_id?: number | null }; rr_hub: unknown };
}
