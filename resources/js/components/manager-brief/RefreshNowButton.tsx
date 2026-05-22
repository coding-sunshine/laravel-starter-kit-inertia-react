import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';

interface Props {
    canRefresh: boolean;
}

export function RefreshNowButton({ canRefresh }: Props) {
    const [processing, setProcessing] = useState(false);

    if (!canRefresh) {
        return null;
    }

    const handleClick = () => {
        if (processing) return;
        setProcessing(true);
        router.post(
            '/manager-brief/refresh',
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={processing}
            onClick={handleClick}
            data-pan="manager-brief-refresh-now"
        >
            {processing ? (
                <>
                    <svg
                        className="mr-1.5 h-3.5 w-3.5 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <circle
                            className="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            strokeWidth="4"
                        />
                        <path
                            className="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                    </svg>
                    Refreshing…
                </>
            ) : (
                'Refresh insights'
            )}
        </Button>
    );
}
