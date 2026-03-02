import { useEffect, useState } from 'react';

/** Matches Tailwind lg (1024px). Sidebar is drawer below this, persistent above. */
const MOBILE_BREAKPOINT = 1024;

export function useIsMobile() {
    const [isMobile, setIsMobile] = useState<boolean>();

    useEffect(() => {
        const mql = window.matchMedia(
            `(max-width: ${MOBILE_BREAKPOINT - 1}px)`,
        );

        const onChange = () => {
            setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
        };

        mql.addEventListener('change', onChange);
        // eslint-disable-next-line @eslint-react/hooks-extra/no-direct-set-state-in-use-effect -- initial sync and listener for matchMedia
        setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);

        return () => mql.removeEventListener('change', onChange);
    }, []);

    return !!isMobile;
}
