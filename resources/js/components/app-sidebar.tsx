import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { OrganizationSwitcher } from '@/components/organization-switcher';
import { SidingSwitcher } from '@/components/siding-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as changelogIndex } from '@/routes/changelog';
import { create as contactCreate } from '@/routes/contact';
import { index as helpIndex } from '@/routes/help';
import organizations from '@/routes/organizations';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    BookOpen,
    Building2,
    CreditCard,
    FileText,
    Folder,
    LayoutGrid,
    LifeBuoy,
    Mail,
    Megaphone,
    Scale,
    Train,
    Truck,
    Settings,
    Database,
    Factory,
    MapPin,
    Package,
    Timer,
    Route,
} from 'lucide-react';
import { useMemo } from 'react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
        icon: LayoutGrid,
        dataPan: 'nav-dashboard',
    },
    {
        title: 'Power Plants',
        href: '/master-data/power-plants',
        icon: Factory,
        dataPan: 'nav-power-plants',
    },
    {
        title: 'Sidings',
        href: '/master-data/sidings',
        icon: MapPin,
        dataPan: 'nav-sidings',
    },
    {
        title: 'Loaders',
        href: '/master-data/loaders',
        icon: Package,
        dataPan: 'nav-loaders',
    },
    {
        title: 'Penalty Types',
        href: '/master-data/penalty-types',
        icon: AlertTriangle,
        dataPan: 'nav-penalty-types',
    },
    {
        title: 'Section Timers',
        href: '/master-data/section-timers',
        icon: Timer,
        dataPan: 'nav-section-timers',
    },
    {
        title: 'Distance Matrix',
        href: '/master-data/distance-matrix',
        icon: Route,
        dataPan: 'nav-distance-matrix',
    },
    // {
    //     title: 'Organizations',
    //     href: organizations.index.url(),
    //     icon: Building2,
    //     tenancyRequired: true,
    //     dataPan: 'nav-organizations',
    // },
    {
        title: 'Billing',
        href: '/billing',
        icon: CreditCard,
        tenancyRequired: true,
        dataPan: 'nav-billing',
    },
    {
        title: 'Rakes',
        href: '/rakes',
        icon: Train,
        dataPan: 'nav-rakes',
    },
    {
        title: 'Indents',
        href: '/indents',
        icon: FileText,
        dataPan: 'nav-indents',
    },
    {
        title: 'Road Dispatch',
        href: '/road-dispatch/daily-vehicle-entries',
        icon: Truck,
        dataPan: 'nav-road-dispatch',
    },
    {
        title: 'Railway Receipts',
        href: '/railway-receipts',
        icon: FileText,
        dataPan: 'nav-railway-receipts',
    },
    {
        title: 'Penalties',
        href: '/penalties',
        icon: AlertTriangle,
        dataPan: 'nav-penalties',
    },
    {
        title: 'Alerts',
        href: '/alerts',
        icon: AlertTriangle,
        dataPan: 'nav-alerts',
    },
    {
        title: 'Reconciliation',
        href: '/reconciliation',
        icon: Scale,
        dataPan: 'nav-reconciliation',
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: BarChart3,
        dataPan: 'nav-reports',
    },
    {
        title: 'Changelog',
        href: changelogIndex().url,
        icon: Megaphone,
        permission: 'changelog.index',
        feature: 'changelog',
        dataPan: 'nav-changelog',
    },
    {
        title: 'Help',
        href: helpIndex().url,
        icon: LifeBuoy,
        permission: 'help.index',
        feature: 'help',
        dataPan: 'nav-help',
    },
    {
        title: 'Contact',
        href: contactCreate().url,
        icon: Mail,
        permission: 'contact.create',
        feature: 'contact',
        dataPan: 'nav-contact',
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'API docs',
    //     href: '/docs/api',
    //     icon: BookOpen,
    //     feature: 'scramble_api_docs',
    //     dataPan: 'nav-api-docs',
    // },
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/react-starter-kit',
    //     icon: Folder,
    //     dataPan: 'nav-repository',
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#react',
    //     icon: BookOpen,
    //     dataPan: 'nav-documentation',
    // },
];

/** Hide item when it has a feature key and that feature is inactive (shared from server). */
function canShowNavItem(
    item: NavItem,
    permissions: string[],
    canBypass: boolean,
    features: SharedData['features'],
    tenancyEnabled: boolean,
): boolean {
    if (item.tenancyRequired && !tenancyEnabled) {
        return false;
    }
    if (item.feature && !features?.[item.feature]) {
        return false;
    }
    if (canBypass || !item.permission) {
        return true;
    }
    const required = Array.isArray(item.permission)
        ? item.permission
        : [item.permission];
    return required.some((p) => permissions.includes(p));
}

export function AppSidebar() {
    const { auth, features } = usePage<SharedData>().props;
    const visibleMainNavItems = useMemo(
        () =>
            mainNavItems.filter((item) =>
                canShowNavItem(
                    item,
                    auth.permissions ?? [],
                    auth.can_bypass ?? false,
                    features ?? {},
                    auth.tenancy_enabled ?? true,
                ),
            ),
        [auth.permissions, auth.can_bypass, auth.tenancy_enabled, features],
    );

    const visibleFooterNavItems = useMemo(() => {
        const f = features ?? {};
        return footerNavItems.filter(
            (item) =>
                !item.feature || Boolean(f[item.feature as keyof typeof f]),
        );
    }, [features]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard().url} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    {(auth.tenancy_enabled ?? true) && (
                        <SidebarMenuItem>
                            <OrganizationSwitcher />
                        </SidebarMenuItem>
                    )}
                    <SidebarMenuItem>
                        <SidingSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleMainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={visibleFooterNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
