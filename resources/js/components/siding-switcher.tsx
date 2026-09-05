import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Train } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const SIDING_SWITCH_URL = '/siding/switch';

export function SidingSwitcher() {
    const { auth } = usePage<SharedData>().props;
    const userSidings = auth.sidings ?? [];
    const current = auth.current_siding;

    // Hide when user has only 1 siding (auto-locked)
    if (userSidings.length <= 1) {
        return null;
    }

    const canViewAll = auth.can_view_all_sidings ?? false;

    const switchSiding = (sidingId: number | null) => {
        router.post(
            SIDING_SWITCH_URL,
            { siding_id: sidingId },
            { preserveScroll: true },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    className="flex w-full items-center justify-between gap-2 px-2"
                    data-pan="siding-switcher"
                >
                    <span className="flex items-center gap-2 truncate">
                        <Train className="size-4 shrink-0" />
                        <span className="truncate">
                            {current?.name ?? 'All sidings'}
                        </span>
                    </span>
                    <ChevronsUpDown className="size-4 shrink-0 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="start"
                className="w-[--radix-dropdown-menu-trigger-width]"
            >
                {canViewAll && (
                    <DropdownMenuItem
                        onSelect={() => switchSiding(null)}
                        className="flex cursor-pointer items-center gap-2"
                    >
                        {current === null || current === undefined ? (
                            <Check className="size-4 shrink-0" />
                        ) : (
                            <span className="size-4 shrink-0" />
                        )}
                        <span className="truncate">All sidings</span>
                    </DropdownMenuItem>
                )}
                {userSidings.map((siding) => (
                    <DropdownMenuItem
                        key={siding.id}
                        onSelect={() => switchSiding(siding.id)}
                        className="flex cursor-pointer items-center gap-2"
                    >
                        {current?.id === siding.id ? (
                            <Check className="size-4 shrink-0" />
                        ) : (
                            <span className="size-4 shrink-0" />
                        )}
                        <span className="truncate">{siding.name}</span>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
