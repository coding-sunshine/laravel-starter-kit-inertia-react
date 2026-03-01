import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import type { FleetNavItem } from '@/config/fleet-nav';
import { type NavItem } from '@/types';
import { ChevronRight } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function getHref(item: NavItem): string {
    return typeof item.href === 'string' ? item.href : item.href.url;
}

export function NavMain({
    items = [],
    fleetSubItems = [],
}: {
    items: NavItem[];
    fleetSubItems?: FleetNavItem[];
}) {
    const page = usePage();
    const isFleetPath = page.url.startsWith('/fleet');
    const [fleetOpen, setFleetOpen] = useState(isFleetPath);

    useEffect(() => {
        if (isFleetPath) setFleetOpen(true);
    }, [isFleetPath]);

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const href = getHref(item);
                    const isFleet = href === '/fleet' && fleetSubItems.length > 0;

                    if (isFleet) {
                        return (
                            <Collapsible
                                key={item.title}
                                open={fleetOpen}
                                onOpenChange={setFleetOpen}
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton
                                            isActive={isFleetPath}
                                            tooltip={{ children: item.title }}
                                            className="group min-h-9"
                                        >
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                            <ChevronRight
                                                className="ml-auto size-4 shrink-0 transition-transform group-data-[state=open]:rotate-90"
                                                aria-hidden
                                            />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <SidebarMenuSub>
                                            {fleetSubItems.map((sub) => {
                                                const subActive =
                                                    page.url === sub.href ||
                                                    (sub.href !== '/fleet' &&
                                                        page.url.startsWith(
                                                            sub.href + '/',
                                                        ));
                                                return (
                                                    <SidebarMenuSubItem
                                                        key={sub.href}
                                                    >
                                                        <SidebarMenuSubButton
                                                            asChild
                                                            isActive={subActive}
                                                        >
                                                            <Link
                                                                href={sub.href}
                                                                prefetch="click"
                                                            >
                                                                {sub.icon && (
                                                                    <sub.icon />
                                                                )}
                                                                <span>
                                                                    {sub.title}
                                                                </span>
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </SidebarMenuSubItem>
                                                );
                                            })}
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>
                        );
                    }

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={page.url.startsWith(href)}
                                tooltip={{ children: item.title }}
                            >
                                <Link
                                    href={item.href}
                                    prefetch="click"
                                    {...(item.dataPan
                                        ? { 'data-pan': item.dataPan }
                                        : {})}
                                >
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
