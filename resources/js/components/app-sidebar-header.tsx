import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useEffect, useState } from 'react';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const fleetOnly = Boolean(usePage<SharedData>().props.fleet_only_app);
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        if (!fleetOnly) return;
        const t = setInterval(() => setNow(new Date()), 1000);
        return () => clearInterval(t);
    }, [fleetOnly]);

    const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    const dateStr = now.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });

    return (
        <header
            className={
                'flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4 ' +
                (fleetOnly ? 'fleet-chrome-header' : '')
            }
        >
            <div className="flex flex-1 items-center gap-2 min-w-0">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <SidebarTrigger className="-ml-1 shrink-0" title="Toggle sidebar" />
                    </TooltipTrigger>
                    <TooltipContent side="right">
                        Toggle sidebar
                    </TooltipContent>
                </Tooltip>
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            {fleetOnly && (
                <div className="flex shrink-0 items-center gap-4 md:gap-3">
                    <span className="text-sm tabular-nums text-white/90">{timeStr}</span>
                    <span className="hidden text-sm text-white/80 sm:inline">{dateStr}</span>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                className="flex items-center gap-1.5 text-white/90 hover:bg-white/10 hover:text-white"
                            >
                                <span className="hidden sm:inline">{auth.user.name}</span>
                                <ChevronDown className="size-4 opacity-80" />
                                <Avatar className="size-8 rounded-full bg-blue-600">
                                    <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                                    <AvatarFallback className="rounded-full bg-blue-600 text-white">
                                        {getInitials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="w-56" align="end">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            )}
        </header>
    );
}
