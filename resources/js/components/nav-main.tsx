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
import { type NavGroup, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

function isItemActive(url: string, href: string): boolean {
    if (href === '/dashboard') return url === '/dashboard';
    return url.startsWith(href);
}

function NavFlatItem({ item, url }: { item: NavItem; url: string }) {
    const href = typeof item.href === 'string' ? item.href : item.href.url;
    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                asChild
                isActive={isItemActive(url, href)}
                tooltip={{ children: item.title }}
            >
                <Link
                    href={href}
                    prefetch="click"
                    {...(item.dataPan ? { 'data-pan': item.dataPan } : {})}
                >
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

function NavGroupItem({ group, url }: { group: NavGroup; url: string }) {
    const isOpen = group.items.some((item) => {
        const href = typeof item.href === 'string' ? item.href : item.href.url;
        return isItemActive(url, href);
    });

    return (
        <Collapsible asChild defaultOpen={isOpen} className="group/collapsible">
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton tooltip={{ children: group.title }}>
                        {group.icon && <group.icon />}
                        <span>{group.title}</span>
                        <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub>
                        {group.items.map((item) => {
                            const href =
                                typeof item.href === 'string'
                                    ? item.href
                                    : item.href.url;
                            return (
                                <SidebarMenuSubItem key={item.title}>
                                    <SidebarMenuSubButton
                                        asChild
                                        isActive={isItemActive(url, href)}
                                    >
                                        <Link
                                            href={href}
                                            prefetch="click"
                                            {...(item.dataPan
                                                ? {
                                                      'data-pan':
                                                          item.dataPan,
                                                  }
                                                : {})}
                                        >
                                            <span>{item.title}</span>
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

export function NavMain({
    items = [],
    groups = [],
    label = 'CRM',
}: {
    items?: NavItem[];
    groups?: NavGroup[];
    label?: string;
}) {
    const page = usePage();
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <NavFlatItem
                        key={item.title}
                        item={item}
                        url={page.url}
                    />
                ))}
                {groups.map((group) => (
                    <NavGroupItem
                        key={group.title}
                        group={group}
                        url={page.url}
                    />
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
