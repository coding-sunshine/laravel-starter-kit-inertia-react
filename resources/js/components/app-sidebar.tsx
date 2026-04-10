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
import { type NavGroup, type NavItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    ClipboardList,
    CreditCard,
    Factory,
    FileText,
    History,
    LayoutGrid,
    MapPin,
    Mountain,
    Package,
    Route,
    Scale,
    Settings,
    Timer,
    Train,
    Truck,
} from 'lucide-react';
import { useMemo } from 'react';
import AppLogo from './app-logo';

/**
 * Section-based permissions (sections.{slug}.{action}).
 * Must match config/section_permissions.php nav_permission and backend checks.
 */
const platformNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
        icon: LayoutGrid,
        permission: 'sections.dashboard.view',
        dataPan: 'nav-dashboard',
    },
    {
        title: 'Settings',
        href: '#',
        icon: Settings,
        collapsible: true,
        subItems: [
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
                title: 'Shift Timings',
                href: '/master-data/shift-timings',
                icon: Timer,
                permission: 'sections.shift_timings.view',
                dataPan: 'nav-shift-timings',
            },
            {
                title: 'Opening Coal Stock',
                href: '/master-data/opening-coal-stock',
                icon: Scale,
                permission: 'sections.opening_coal_stock.view',
                dataPan: 'nav-opening-coal-stock',
            },
            {
                title: 'Daily Stock Details',
                href: '/master-data/daily-stock-details',
                icon: Scale,
                permission: 'sections.daily_stock_details.view',
                dataPan: 'nav-daily-stock-details',
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
                title: 'Transpoters',
                href: '/vehicle-workorders',
                icon: FileText,
                permission: 'sections.transport.view',
                dataPan: 'nav-vehicle-workorders',
            },
            {
                title: 'Reports',
                href: '/reports',
                icon: BarChart3,
                permission: 'sections.reports.view',
                dataPan: 'nav-reports',
            },
        ],
    },
    {
        title: 'Historic',
        href: '#',
        icon: History,
        collapsible: true,
        permission: [
            'sections.historical_mines.view',
            'sections.historical_railway_siding.view',
        ],
        subItems: [
            {
                title: 'Mines historical',
                href: '/historical/mines',
                icon: Train,
                permission: 'sections.historical_mines.view',
                dataPan: 'nav-historical-mines',
            },
            {
                title: 'Railway siding historical',
                href: '/historical/railway-siding',
                icon: Train,
                permission: 'sections.historical_railway_siding.view',
                dataPan: 'nav-historical-railway-siding',
            },
        ],
    },
    {
        title: 'Indent',
        href: '/siding-pre-indent-reports',
        icon: ClipboardList,
        permission: 'sections.siding_pre_indent_reports.view',
        dataPan: 'nav-siding-pre-indent-reports',
    },
    {
        title: 'E-Demand',
        href: '/indents',
        icon: FileText,
        permission: 'sections.indents.view',
        dataPan: 'nav-indents',
    },
    {
        title: 'Rake Weighments',
        href: '/weighments',
        icon: Scale,
        permission: 'sections.weighments.view',
        dataPan: 'nav-weighments',
    },
    {
        title: 'Rake Loader',
        href: '/rake-loader',
        icon: Package,
        permission: 'sections.rake_loader.view',
        dataPan: 'nav-rake-loader',
    },
    {
        title: 'Rake Progress',
        href: '/rakes',
        icon: Train,
        permission: 'sections.rakes.view',
        dataPan: 'nav-rakes',
    },
    {
        title: 'Railway Receipts',
        href: '/railway-receipts',
        icon: FileText,
        permission: 'sections.railway_receipts.view',
        dataPan: 'nav-railway-receipts',
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
        title: 'Mines Dispatch Data',
        href: '/vehicle-dispatch',
        icon: Truck,
        permission: 'sections.mines_dispatch_data.view',
        dataPan: 'nav-vehicle-dispatch',
    },
    // {
    //     title: 'Penalties',
    //     href: '/penalties',
    //     icon: AlertTriangle,
    //     permission: 'sections.penalties.view',
    //     dataPan: 'nav-penalties',
    // },
    // {
    //     title: 'Alerts',
    //     href: '/alerts',
    //     icon: AlertTriangle,
    //     permission: 'sections.alerts.view',
    //     dataPan: 'nav-alerts',
    // },
    // {
    //     title: 'Reconciliation',
    //     href: '/reconciliation',
    //     icon: Scale,
    //     permission: 'sections.reconciliation.view',
    //     dataPan: 'nav-reconciliation',
    // },

    // {
    //     title: 'Changelog',
    //     href: changelogIndex().url,
    //     icon: Megaphone,
    //     permission: 'sections.changelog.view',
    //     feature: 'changelog',
    //     dataPan: 'nav-changelog',
    // },
    // {
    //     title: 'Help',
    //     href: helpIndex().url,
    //     icon: LifeBuoy,
    //     permission: 'sections.help.view',
    //     feature: 'help',
    //     dataPan: 'nav-help',
    // },
    // {
    //     title: 'Contact',
    //     href: contactCreate().url,
    //     icon: Mail,
    //     permission: 'sections.contact.create',
    //     feature: 'contact',
    //     dataPan: 'nav-contact',
    // },
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
    roles: string[],
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
    if (item.roles && item.roles.length > 0 && !canBypass) {
        const hasRole = item.roles.some((role) => roles.includes(role));

        if (!hasRole) {
            return false;
        }
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
    const canShow = useMemo(
        () => (item: NavItem) =>
            canShowNavItem(
                item,
                auth.permissions ?? [],
                auth.roles ?? [],
                auth.can_bypass ?? false,
                features ?? {},
                auth.tenancy_enabled ?? true,
            ),
        [
            auth.permissions,
            auth.roles,
            auth.can_bypass,
            auth.tenancy_enabled,
            features,
        ],
    );

    const navGroups = useMemo((): NavGroup[] => {
        const [dashboardItem, settingsItem, ...restPlatform] = platformNavItems;
        const visibleSettingsSubItems =
            settingsItem?.collapsible && settingsItem.subItems
                ? settingsItem.subItems.filter(canShow)
                : [];
        const showSettings = visibleSettingsSubItems.length > 0;

        const platformItems: NavItem[] = [];
        if (dashboardItem && canShow(dashboardItem)) {
            platformItems.push(dashboardItem);
        }
        if (showSettings && settingsItem) {
            platformItems.push({
                ...settingsItem,
                subItems: visibleSettingsSubItems,
            });
        }
        restPlatform
            .filter(canShow)
            .forEach((item) => platformItems.push(item));

        if (platformItems.length === 0) return [];
        return [{ title: 'Platform', items: platformItems }];
    }, [canShow]);

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
                        <SidebarMenuButton
                            size="lg"
                            type="button"
                            tooltip="SHAReReport"
                            className="cursor-default"
                        >
                            <AppLogo />
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    {/* Hidden for now (keep code, hide UI) */}
                    {false && (auth.tenancy_enabled ?? true) && (
                        <SidebarMenuItem>
                            <OrganizationSwitcher />
                        </SidebarMenuItem>
                    )}
                    {/* Hidden for now (keep code, hide UI) */}
                    {false && (
                        <SidebarMenuItem>
                            <SidingSwitcher />
                        </SidebarMenuItem>
                    )}
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={navGroups} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={visibleFooterNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
