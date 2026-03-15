import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export default function AppLogo() {
    const { name, branding } = usePage<SharedData>().props;
    const logoUrl = branding?.logoUrl ?? '/images/logo.png';
    const logoUrlDark = branding?.logoUrlDark ?? '/images/logo-light.png';
    const siteName = name ?? 'Fusion CRM';

    return (
        <>
            <div className="flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-md">
                <img
                    src={logoUrl}
                    alt={siteName}
                    className="size-full object-contain dark:hidden"
                />
                <img
                    src={logoUrlDark}
                    alt={siteName}
                    className="hidden size-full object-contain dark:block"
                />
            </div>
            <div className="ml-1 grid min-w-0 flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {siteName}
                </span>
            </div>
        </>
    );
}
