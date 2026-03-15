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
import { ChevronDown } from 'lucide-react';

function NavMenuItems({ items }: { items: NavItem[] }) {
    const page = usePage();

    return (
        <>
            {items.map((item) => {
                const href =
                    typeof item.href === 'string' ? item.href : item.href.url;
                const key = `${item.title}-${href}`;

                if (item.collapsible && item.subItems?.length) {
                    return (
                        <Collapsible
                            key={key}
                            asChild
                            defaultOpen={false}
                            className="group/collapsible"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton
                                        tooltip={{ children: item.title }}
                                    >
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                        <ChevronDown className="ml-auto size-4 transition-transform group-data-[state=open]/collapsible:rotate-180" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {item.subItems.map((sub) => {
                                            const subHref =
                                                typeof sub.href === 'string'
                                                    ? sub.href
                                                    : sub.href.url;
                                            return (
                                                <SidebarMenuSubItem
                                                    key={`${sub.title}-${subHref}`}
                                                >
                                                    <SidebarMenuSubButton
                                                        asChild
                                                        isActive={page.url.startsWith(
                                                            subHref,
                                                        )}
                                                    >
                                                        <Link
                                                            href={sub.href}
                                                            prefetch
                                                            {...(sub.dataPan
                                                                ? {
                                                                      'data-pan':
                                                                          sub.dataPan,
                                                                  }
                                                                : {})}
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
                    <SidebarMenuItem key={key}>
                        <SidebarMenuButton
                            asChild
                            isActive={page.url.startsWith(href)}
                            tooltip={{ children: item.title }}
                        >
                            <Link
                                href={item.href}
                                prefetch
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
        </>
    );
}

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
    return (
        <>
            {groups.map((group) => (
                <SidebarGroup key={group.title} className="px-2 py-0">
                    <SidebarGroupLabel>{group.title}</SidebarGroupLabel>
                    <SidebarMenu>
                        <NavMenuItems items={group.items} />
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
