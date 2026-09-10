import { JsonFetchError, laravelJsonFetch } from '@/lib/laravel-json-fetch';
import { useEffect, useRef, useState } from 'react';

/**
 * Loads deferred dashboard tab props via `GET /dashboard/section-data` when the user
 * switches sections client-side (without a full Inertia visit).
 */
export function useDashboardSectionPayload(options: {
    activeSection: string;
    serverSection: string | undefined;
    visibleSectionIds: Set<string>;
    /** When this string changes (filters or URL), lazy patch resets. */
    filterSignature: string;
    buildSectionDataUrl: (section: string) => string;
    /** Sections that never need a JSON bundle (empty catalog). */
    emptyBundleSections?: Set<string>;
}): {
    sectionDeferredPatch: Record<string, unknown> | null;
    isSectionLoading: boolean;
} {
    const {
        activeSection,
        serverSection,
        visibleSectionIds,
        filterSignature,
        buildSectionDataUrl,
        emptyBundleSections,
    } = options;

    const [patchBySection, setPatchBySection] = useState<
        Record<string, Record<string, unknown>>
    >({});
    const [inflightSection, setInflightSection] = useState<string | null>(null);
    const fulfilledKeyRef = useRef<Set<string>>(new Set());

    useEffect(() => {
        fulfilledKeyRef.current = new Set();
        setPatchBySection({});
    }, [filterSignature]);

    const cacheKey = `${activeSection}::${filterSignature}`;

    useEffect(() => {
        if (!visibleSectionIds.has(activeSection)) {
            return;
        }
        if (emptyBundleSections?.has(activeSection)) {
            return;
        }
        if (activeSection === serverSection) {
            return;
        }
        if (fulfilledKeyRef.current.has(cacheKey)) {
            return;
        }

        const targetSection = activeSection;
        let cancelled = false;
        setInflightSection(targetSection);
        const url = buildSectionDataUrl(targetSection);

        laravelJsonFetch<Record<string, unknown>>(url)
            .then((data) => {
                if (cancelled) {
                    return;
                }
                fulfilledKeyRef.current.add(cacheKey);
                setPatchBySection((prev) => ({
                    ...prev,
                    [targetSection]: data,
                }));
            })
            .catch((err: unknown) => {
                if (import.meta.env.DEV && !(err instanceof JsonFetchError)) {
                    console.error(err);
                }
                if (cancelled) {
                    return;
                }
                fulfilledKeyRef.current.add(cacheKey);
                setPatchBySection((prev) => ({
                    ...prev,
                    [targetSection]: {},
                }));
            })
            .finally(() => {
                if (!cancelled) {
                    setInflightSection((s) =>
                        s === targetSection ? null : s,
                    );
                }
            });

        return () => {
            cancelled = true;
        };
    }, [
        activeSection,
        serverSection,
        visibleSectionIds,
        filterSignature,
        cacheKey,
        buildSectionDataUrl,
        emptyBundleSections,
    ]);

    const sectionDeferredPatch = patchBySection[activeSection] ?? null;
    const isSectionLoading = inflightSection === activeSection;

    return { sectionDeferredPatch, isSectionLoading };
}
