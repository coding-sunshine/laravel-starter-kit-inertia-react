import { useEffect, useState } from 'react';

/**
 * Respects prefers-reduced-motion: reduce for accessibility (UI-06).
 * When true, animations should be disabled or duration set to 0.
 */
export function useReducedMotion(): boolean {
    const [reduced, setReduced] = useState(false);

    useEffect(() => {
        const mql = window.matchMedia('(prefers-reduced-motion: reduce)');
        const onChange = () => setReduced(mql.matches);
        mql.addEventListener('change', onChange);
        setReduced(mql.matches);
        return () => mql.removeEventListener('change', onChange);
    }, []);

    return reduced;
}
