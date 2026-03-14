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
    Mountain,
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

/**
 * Section-based permissions (sections.{slug}.{action}).
 * Must match config/section_permissions.php nav_permission and backend checks.
 */
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
        icon: LayoutGrid,
        permission: 'sections.dashboard.view',
        dataPan: 'nav-dashboard',
    },
    {
        title: 'Power Plants',
        href: '/master-data/power-plants',
        icon: Factory,
        permission: 'sections.power_plants.view',
        dataPan: 'nav-power-plants',
    },
    {
        title: 'Sidings',
        href: '/master-data/sidings',
        icon: MapPin,
        permission: 'sections.sidings.view',
        dataPan: 'nav-sidings',
    },
    {
        title: 'Loaders',
        href: '/master-data/loaders',
        icon: Package,
        permission: 'sections.loaders.view',
        dataPan: 'nav-loaders',
    },
    {
        title: 'Penalty Types',
        href: '/master-data/penalty-types',
        icon: AlertTriangle,
        permission: 'sections.penalty_types.view',
        dataPan: 'nav-penalty-types',
    },
    {
        title: 'Section Timers',
        href: '/master-data/section-timers',
        icon: Timer,
        permission: 'sections.section_timers.view',
        dataPan: 'nav-section-timers',
    },
    {
        title: 'Distance Matrix',
        href: '/master-data/distance-matrix',
        icon: Route,
        permission: 'sections.distance_matrix.view',
        dataPan: 'nav-distance-matrix',
    },
    {
        title: 'Billing',
        href: '/billing',
        icon: CreditCard,
        tenancyRequired: true,
        permission: 'sections.billing.view',
        dataPan: 'nav-billing',
    },
    {
        title: 'Rakes',
        href: '/rakes',
        icon: Train,
        permission: 'sections.rakes.view',
        dataPan: 'nav-rakes',
    },
    {
        title: 'Indents',
        href: '/indents',
        icon: FileText,
        permission: 'sections.indents.view',
        dataPan: 'nav-indents',
    },
    {
        title: 'Railway Siding Record Data',
        href: '/road-dispatch/daily-vehicle-entries',
        icon: Truck,
        permission: 'sections.railway_siding_record_data.view',
        dataPan: 'nav-road-dispatch',
    },
    {
        title: 'Railway Siding Empty Weighment',
        href: '/railway-siding-empty-weighment',
        icon: Scale,
        permission: 'sections.railway_siding_empty_weighment.view',
        dataPan: 'nav-railway-siding-empty-weighment',
    },
    {
        title: 'Production - Coal',
        href: '/production/coal',
        icon: Factory,
        permission: 'sections.production_coal.view',
        dataPan: 'nav-production-coal',
    },
    {
        title: 'Production - OB',
        href: '/production/ob',
        icon: Mountain,
        permission: 'sections.production_ob.view',
        dataPan: 'nav-production-ob',
    },
    {
        title: 'Mines Dispatch Data',
        href: '/vehicle-dispatch',
        icon: Truck,
        permission: 'sections.mines_dispatch_data.view',
        dataPan: 'nav-vehicle-dispatch',
    },
    {
        title: 'Transport',
        href: '/vehicle-workorders',
        icon: FileText,
        permission: 'sections.transport.view',
        dataPan: 'nav-vehicle-workorders',
    },
    {
        title: 'Railway Receipts',
        href: '/railway-receipts',
        icon: FileText,
        permission: 'sections.railway_receipts.view',
        dataPan: 'nav-railway-receipts',
    },
    {
        title: 'Penalties',
        href: '/penalties',
        icon: AlertTriangle,
        permission: 'sections.penalties.view',
        dataPan: 'nav-penalties',
    },
    {
        title: 'Alerts',
        href: '/alerts',
        icon: AlertTriangle,
        permission: 'sections.alerts.view',
        dataPan: 'nav-alerts',
    },
    {
        title: 'Reconciliation',
        href: '/reconciliation',
        icon: Scale,
        permission: 'sections.reconciliation.view',
        dataPan: 'nav-reconciliation',
    },
    {
        title: 'Weighments',
        href: '/weighments',
        icon: Scale,
        permission: 'sections.weighments.view',
        dataPan: 'nav-weighments',
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: BarChart3,
        permission: 'sections.reports.view',
        dataPan: 'nav-reports',
    },
    {
        title: 'Changelog',
        href: changelogIndex().url,
        icon: Megaphone,
        permission: 'sections.changelog.view',
        feature: 'changelog',
        dataPan: 'nav-changelog',
    },
    {
        title: 'Help',
        href: helpIndex().url,
        icon: LifeBuoy,
        permission: 'sections.help.view',
        feature: 'help',
        dataPan: 'nav-help',
    },
    {
        title: 'Contact',
        href: contactCreate().url,
        icon: Mail,
        permission: 'sections.contact.create',
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
