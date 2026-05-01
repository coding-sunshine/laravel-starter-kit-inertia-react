import { useCommandPaletteStore } from '@/stores/command-palette-store';
import { useEffect } from 'react';

export function useCommandShortcut() {
    const toggle = useCommandPaletteStore((s) => s.toggle);

    useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            if (
                (event.metaKey || event.ctrlKey) &&
                event.key.toLowerCase() === 'k'
            ) {
                event.preventDefault();
                toggle();
            }
        };

        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [toggle]);
}
