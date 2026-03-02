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
import type { FleetNavItem, FleetNavSection } from '@/config/fleet-nav';
import { type NavItem } from '@/types';
import { ChevronRight } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function getHref(item: NavItem): string {
    return typeof item.href === 'string' ? item.href : item.href.url;
}

function isItemActive(pageUrl: string, href: string): boolean {
    return pageUrl === href || (href !== '/fleet' && pageUrl.startsWith(href + '/'));
}

export function NavMain({
    items = [],
    fleetSubItems = [],
    groupLabel = 'Platform',
    fleetOnlyLayout = false,
    fleetDashboardItem: dashboardItem,
    fleetNavSections: sections = [],
}: {
    items: NavItem[];
    fleetSubItems?: FleetNavItem[];
    groupLabel?: string;
    fleetOnlyLayout?: boolean;
    fleetDashboardItem?: FleetNavItem;
    fleetNavSections?: FleetNavSection[];
}) {
    const page = usePage();
    const pageUrl = page.url;
    const isFleetPath = pageUrl.startsWith('/fleet');
    const [fleetOpen, setFleetOpen] = useState(isFleetPath);
    const [openSections, setOpenSections] = useState<Record<string, boolean>>({});

    useEffect(() => {
        if (isFleetPath) setFleetOpen(true);
    }, [isFleetPath]);

    useEffect(() => {
        if (!fleetOnlyLayout || !sections.length) return;
        const next: Record<string, boolean> = {};
        sections.forEach((sec) => {
            const hasActive = sec.items.some((it) => isItemActive(pageUrl, it.href));
            if (hasActive) next[sec.label] = true;
        });
        setOpenSections((prev) => ({ ...prev, ...next }));
    }, [fleetOnlyLayout, sections, pageUrl]);

    if (fleetOnlyLayout && dashboardItem && sections.length > 0) {
        return (
            <>
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Main</SidebarGroupLabel>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                asChild
                                isActive={pageUrl === dashboardItem.href}
                                tooltip={{ children: dashboardItem.title }}
                            >
                                <Link href={dashboardItem.href} prefetch="click">
                                    {dashboardItem.icon && <dashboardItem.icon />}
                                    <span>{dashboardItem.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>
                {sections.map((section) => {
                    const isOpen = openSections[section.label] ?? false;
                    const hasSingleItem = section.items.length === 1;
                    return (
                        <SidebarGroup key={section.label} className="px-2 py-0">
                            <SidebarMenu>
                                {hasSingleItem ? (() => {
                                    const item = section.items[0]!;
                                    const ItemIcon = item.icon;
                                    return (
                                        <SidebarMenuItem>
                                            <SidebarMenuButton
                                                asChild
                                                isActive={isItemActive(pageUrl, item.href)}
                                                tooltip={{ children: item.title }}
                                            >
                                                <Link href={item.href} prefetch="click">
                                                    {ItemIcon && <ItemIcon />}
                                                    <span>{item.title}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    );
                                })() : (
                                    <Collapsible
                                        open={isOpen}
                                        onOpenChange={(open) =>
                                            setOpenSections((p) => ({ ...p, [section.label]: open }))
                                        }
                                    >
                                        <SidebarMenuItem>
                                            <CollapsibleTrigger asChild>
                                                <SidebarMenuButton
                                                    isActive={section.items.some((it) =>
                                                        isItemActive(pageUrl, it.href),
                                                    )}
                                                    tooltip={{ children: section.label }}
                                                    className="group min-h-9"
                                                >
                                                    <span>{section.label}</span>
                                                    <ChevronRight
                                                        className="ml-auto size-4 shrink-0 transition-transform group-data-[state=open]:rotate-90"
                                                        aria-hidden
                                                    />
                                                </SidebarMenuButton>
                                            </CollapsibleTrigger>
                                            <CollapsibleContent>
                                                <SidebarMenuSub>
                                                    {section.items.map((sub) => (
                                                        <SidebarMenuSubItem key={sub.href}>
                                                            <SidebarMenuSubButton
                                                                asChild
                                                                isActive={isItemActive(pageUrl, sub.href)}
                                                            >
                                                                <Link href={sub.href} prefetch="click">
                                                                    {sub.icon && <sub.icon />}
                                                                    <span>{sub.title}</span>
                                                                </Link>
                                                            </SidebarMenuSubButton>
                                                        </SidebarMenuSubItem>
                                                    ))}
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuItem>
                                    </Collapsible>
                                )}
                            </SidebarMenu>
                        </SidebarGroup>
                    );
                })}
            </>
        );
    }

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{groupLabel}</SidebarGroupLabel>
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
                                                    pageUrl === sub.href ||
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
