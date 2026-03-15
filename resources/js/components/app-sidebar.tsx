import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { OrganizationSwitcher } from '@/components/organization-switcher';
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
import adTemplates from '@/routes/ad-templates';
import agentPortal from '@/routes/agent-portal';
import ai from '@/routes/ai';
import analytics from '@/routes/analytics';
import brochureLayouts from '@/routes/brochure-layouts';
import builderPortal from '@/routes/builder-portal';
import campaignSites from '@/routes/campaign-sites';
import coldOutreach from '@/routes/cold-outreach';
import commissions from '@/routes/commissions';
import contacts from '@/routes/contacts';
import dealTracker from '@/routes/deal-tracker';
import emailCampaigns from '@/routes/email-campaigns';
import enquiries from '@/routes/enquiries';
import funnel from '@/routes/funnel';
import leadGeneration from '@/routes/lead-generation';
import lots from '@/routes/lots';
import memberListings from '@/routes/member-listings';
import nurtureSequences from '@/routes/nurture-sequences';
import pipeline from '@/routes/pipeline';
import projects from '@/routes/projects';
import reports from '@/routes/reports';
import reservations from '@/routes/reservations';
import sales from '@/routes/sales';
import searches from '@/routes/searches';
import tasks from '@/routes/tasks';
import websiteIndex from '@/routes/website-index';
import xero from '@/routes/xero';
import { type NavGroup, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    BookOpen,
    Bot,
    Building2,
    CheckSquare,
    DollarSign,
    ExternalLink,
    FileText,
    Globe,
    LayoutGrid,
    Megaphone,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    Users,
} from 'lucide-react';
import { useMemo } from 'react';
import AppLogo from './app-logo';

const topNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
        icon: LayoutGrid,
        dataPan: 'nav-dashboard',
    },
];

const crmGroups: NavGroup[] = [
    {
        title: 'Contacts',
        icon: Users,
        items: [
            { title: 'All Contacts', href: contacts.index.url(), dataPan: 'nav-contacts' },
            { title: 'Clients', href: '/contacts?type=client', dataPan: 'nav-contacts-clients' },
            { title: 'Subscribers', href: '/contacts?type=subscriber', dataPan: 'nav-contacts-subscribers' },
            { title: 'Sales Agents', href: '/contacts?type=sales_agent', dataPan: 'nav-contacts-sales-agents' },
            { title: 'Developers', href: '/contacts?type=developer', dataPan: 'nav-contacts-developers' },
            { title: 'Pipeline', href: pipeline.index.url(), dataPan: 'nav-pipeline' },
            { title: 'Enquiries', href: enquiries.index.url(), dataPan: 'nav-enquiries' },
            { title: 'Searches', href: searches.index.url(), dataPan: 'nav-searches' },
        ],
    },
    {
        title: 'Properties',
        icon: Building2,
        items: [
            { title: 'Projects', href: projects.table.url(), dataPan: 'nav-projects' },
            { title: 'Lots', href: lots.table.url(), dataPan: 'nav-lots' },
            { title: 'Favourites', href: '/favourites', dataPan: 'nav-favourites' },
            { title: 'Featured', href: '/featured', dataPan: 'nav-featured' },
            { title: 'Potential Properties', href: '/potential-properties', dataPan: 'nav-potential-properties' },
            { title: 'Member Listings', href: memberListings.index.url(), dataPan: 'nav-member-listings' },
        ],
    },
    {
        title: 'Sales',
        icon: DollarSign,
        items: [
            { title: 'Reservations', href: reservations.index.url(), dataPan: 'nav-reservations' },
            { title: 'Sales', href: sales.index.url(), dataPan: 'nav-sales' },
            { title: 'Commissions', href: commissions.index.url(), dataPan: 'nav-commissions' },
            { title: 'Deal Tracker', href: dealTracker.index.url(), dataPan: 'nav-deal-tracker' },
            { title: 'Finance Assessments', href: '/finance-assessments', dataPan: 'nav-finance-assessments' },
        ],
    },
];

const taskNavItems: NavItem[] = [
    {
        title: 'Tasks',
        href: tasks.index.url(),
        icon: CheckSquare,
        dataPan: 'nav-tasks',
    },
];

const toolGroups: NavGroup[] = [
    {
        title: 'AI & Automation',
        icon: Sparkles,
        items: [
            { title: 'AI Assistant', href: ai.bot.index.url(), dataPan: 'nav-ai-bot' },
            { title: 'AI Concierge', href: ai.concierge.index.url(), dataPan: 'nav-ai-concierge' },
            { title: 'Lead Generation', href: leadGeneration.index.url(), dataPan: 'nav-lead-generation' },
            { title: 'Cold Outreach', href: coldOutreach.index.url(), dataPan: 'nav-cold-outreach' },
            { title: 'Nurture Sequences', href: nurtureSequences.index.url(), dataPan: 'nav-nurture-sequences' },
        ],
    },
    {
        title: 'Marketing',
        icon: Megaphone,
        items: [
            { title: 'Email Campaigns', href: emailCampaigns.index.url(), dataPan: 'nav-email-campaigns' },
            { title: 'Ad Templates', href: adTemplates.index.url(), dataPan: 'nav-ad-templates' },
            { title: 'Campaign Sites', href: campaignSites.index.url(), dataPan: 'nav-campaign-sites' },
            { title: 'Brochures', href: brochureLayouts.index.url(), dataPan: 'nav-brochures' },
            { title: 'Mail Status', href: '/mail-status', dataPan: 'nav-mail-status' },
        ],
    },
    {
        title: 'Portals',
        icon: Globe,
        items: [
            { title: 'Agent Portal', href: agentPortal.index.url(), dataPan: 'nav-agent-portal' },
            { title: 'Builder Portal', href: builderPortal.index.url(), dataPan: 'nav-builder-portal' },
            { title: 'Websites', href: websiteIndex.index.url(), dataPan: 'nav-websites' },
        ],
    },
    {
        title: 'Reports',
        icon: BarChart3,
        items: [
            { title: 'Overview', href: reports.index.url(), dataPan: 'nav-reports' },
            { title: 'Reservations', href: '/reports/reservations', dataPan: 'nav-report-reservations' },
            { title: 'Tasks', href: '/reports/tasks', dataPan: 'nav-report-tasks' },
            { title: 'Sales', href: '/reports/sales', dataPan: 'nav-report-sales' },
            { title: 'Commissions', href: '/reports/commissions', dataPan: 'nav-report-commissions' },
            { title: 'Notes', href: '/reports/notes', dataPan: 'nav-report-notes' },
            { title: 'Network Activity', href: '/reports/network-activity', dataPan: 'nav-report-network-activity' },
            { title: 'Login History', href: '/reports/login-history', dataPan: 'nav-report-login' },
            { title: 'Same Device', href: '/reports/same-device', dataPan: 'nav-report-device' },
            { title: 'Analytics', href: analytics.index.url(), dataPan: 'nav-analytics' },
            { title: 'Funnel', href: funnel.index.url(), dataPan: 'nav-funnel' },
        ],
    },
];

const bottomNavItems: NavItem[] = [
    {
        title: 'Resources',
        href: '/resources',
        icon: BookOpen,
        dataPan: 'nav-resources',
    },
    {
        title: 'Xero',
        href: xero.index.url(),
        icon: TrendingUp,
        dataPan: 'nav-xero',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'API docs',
        href: '/docs/api',
        icon: FileText,
        feature: 'scramble_api_docs',
        dataPan: 'nav-api-docs',
    },
];

/** Hide item when it has a feature key and that feature is inactive (shared from server). */
function canShowNavItem(
    item: NavItem,
    permissions: string[],
    canBypass: boolean,
    features: SharedData['features'],
    tenancyEnabled: boolean,
    isSuperAdmin: boolean,
): boolean {
    if (item.superAdminOnly && !isSuperAdmin) {
        return false;
    }
    if (isSuperAdmin) {
        return true;
    }
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
    const permissions = auth.permissions ?? [];
    const canBypass = auth.can_bypass ?? false;
    const resolvedFeatures = features ?? {};
    const tenancyEnabled = auth.tenancy_enabled ?? true;
    const isSuperAdmin = auth.roles?.includes('super-admin') ?? false;

    const filterItems = useMemo(
        () => (items: NavItem[]) =>
            items.filter((item) =>
                canShowNavItem(item, permissions, canBypass, resolvedFeatures, tenancyEnabled, isSuperAdmin),
            ),
        [permissions, canBypass, resolvedFeatures, tenancyEnabled, isSuperAdmin],
    );

    const adminPanelHref = isSuperAdmin ? '/system' : '/admin';
    const adminPanelLabel = isSuperAdmin ? 'System Panel' : 'Admin Panel';
    const canSeeAdminPanel = isSuperAdmin || permissions.includes('access admin panel');

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard().url} prefetch="click">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    {tenancyEnabled && (
                        <SidebarMenuItem>
                            <OrganizationSwitcher />
                        </SidebarMenuItem>
                    )}
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={filterItems(topNavItems)} groups={crmGroups} label="CRM" />
                <NavMain items={filterItems(taskNavItems)} groups={toolGroups} label="Tools" />
                <NavMain items={filterItems(bottomNavItems)} label="Integrations" />
                {canSeeAdminPanel && (
                    <div className="px-2 pb-2">
                        <a
                            href={adminPanelHref}
                            data-pan="nav-admin-panel"
                            className="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        >
                            <ShieldCheck className="size-4 shrink-0" />
                            <span>{adminPanelLabel}</span>
                            <ExternalLink className="ml-auto size-3 shrink-0 opacity-50" />
                        </a>
                    </div>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={filterItems(footerNavItems)} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
