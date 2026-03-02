import type { LucideIcon } from 'lucide-react';
import {
    AlertTriangle,
    BarChart3,
    Battery,
    Bot,
    Car,
    ClipboardList,
    CreditCard,
    FileCheck,
    FileText,
    Fuel,
    GitBranch,
    LayoutGrid,
    MapPin,
    Route,
    Settings,
    Truck,
    Users,
    Wrench,
} from 'lucide-react';

export interface FleetNavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
}

export interface FleetNavSection {
    label: string;
    items: FleetNavItem[];
}

/** Dashboard link shown first in fleet-only sidebar. */
export const fleetDashboardItem: FleetNavItem = {
    title: 'Dashboard',
    href: '/fleet',
    icon: LayoutGrid,
};

/** Fleet sidebar in logical sections (Dashboard is separate). */
export const fleetNavSections: FleetNavSection[] = [
    {
        label: 'Assistant',
        items: [{ title: 'Assistant', href: '/fleet/assistant', icon: Bot }],
    },
    {
        label: 'Operations',
        items: [
            { title: 'Vehicles', href: '/fleet/vehicles', icon: Car },
            { title: 'Drivers', href: '/fleet/drivers', icon: Users },
            { title: 'Driver-vehicle assignments', href: '/fleet/driver-vehicle-assignments', icon: Users },
            { title: 'Routes', href: '/fleet/routes', icon: Route },
            { title: 'Trips', href: '/fleet/trips', icon: Route },
        ],
    },
    {
        label: 'Maintenance',
        items: [
            { title: 'Work orders', href: '/fleet/work-orders', icon: Wrench },
            { title: 'Defects', href: '/fleet/defects', icon: AlertTriangle },
            { title: 'Service schedules', href: '/fleet/service-schedules', icon: ClipboardList },
        ],
    },
    {
        label: 'Fuel & energy',
        items: [
            { title: 'Fuel cards', href: '/fleet/fuel-cards', icon: CreditCard },
            { title: 'Fuel transactions', href: '/fleet/fuel-transactions', icon: Fuel },
            { title: 'Fuel stations', href: '/fleet/fuel-stations', icon: Fuel },
            { title: 'EV charging stations', href: '/fleet/ev-charging-stations', icon: Battery },
        ],
    },
    {
        label: 'Compliance & risk',
        items: [
            { title: 'Compliance items', href: '/fleet/compliance-items', icon: FileCheck },
            { title: 'Insurance policies', href: '/fleet/insurance-policies', icon: FileText },
            { title: 'Incidents', href: '/fleet/incidents', icon: AlertTriangle },
            { title: 'Insurance claims', href: '/fleet/insurance-claims', icon: FileText },
        ],
    },
    {
        label: 'Setup & locations',
        items: [
            { title: 'Locations', href: '/fleet/locations', icon: MapPin },
            { title: 'Cost centers', href: '/fleet/cost-centers', icon: CreditCard },
            { title: 'Garages', href: '/fleet/garages', icon: Wrench },
            { title: 'Geofences', href: '/fleet/geofences', icon: MapPin },
        ],
    },
    {
        label: 'AI & analytics',
        items: [
            { title: 'AI job runs', href: '/fleet/ai-job-runs', icon: BarChart3 },
            { title: 'AI analysis results', href: '/fleet/ai-analysis-results', icon: BarChart3 },
            { title: 'Electrification plan', href: '/fleet/electrification-plan', icon: Battery },
            { title: 'Fleet optimization', href: '/fleet/fleet-optimization', icon: BarChart3 },
        ],
    },
    {
        label: 'Workflows',
        items: [
            { title: 'Workflow definitions', href: '/fleet/workflow-definitions', icon: GitBranch },
            { title: 'Workflow executions', href: '/fleet/workflow-executions', icon: GitBranch },
        ],
    },
    {
        label: 'Alerts & reports',
        items: [
            { title: 'Alerts', href: '/fleet/alerts', icon: AlertTriangle },
            { title: 'Alert preferences', href: '/fleet/alert-preferences', icon: Settings },
            { title: 'Reports', href: '/fleet/reports', icon: FileText },
            { title: 'Report executions', href: '/fleet/report-executions', icon: FileText },
        ],
    },
];

/** Flat list for backward compatibility (e.g. non–fleet-only nav). */
export const fleetNavItems: FleetNavItem[] = [
    fleetDashboardItem,
    ...fleetNavSections.flatMap((s) => s.items),
];
