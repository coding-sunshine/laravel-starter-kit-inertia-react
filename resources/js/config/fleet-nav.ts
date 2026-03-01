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

/** Fleet sidebar sub-navigation. Order and grouping match UI-03. */
export const fleetNavItems: FleetNavItem[] = [
    { title: 'Fleet dashboard', href: '/fleet', icon: LayoutGrid },
    { title: 'Assistant', href: '/fleet/assistant', icon: Bot },
    { title: 'Vehicles', href: '/fleet/vehicles', icon: Car },
    { title: 'Drivers', href: '/fleet/drivers', icon: Users },
    { title: 'Driver-vehicle assignments', href: '/fleet/driver-vehicle-assignments', icon: Users },
    { title: 'Locations', href: '/fleet/locations', icon: MapPin },
    { title: 'Cost centers', href: '/fleet/cost-centers', icon: CreditCard },
    { title: 'Garages', href: '/fleet/garages', icon: Wrench },
    { title: 'Routes', href: '/fleet/routes', icon: Route },
    { title: 'Trips', href: '/fleet/trips', icon: Route },
    { title: 'Geofences', href: '/fleet/geofences', icon: MapPin },
    { title: 'Fuel cards', href: '/fleet/fuel-cards', icon: CreditCard },
    { title: 'Fuel transactions', href: '/fleet/fuel-transactions', icon: Fuel },
    { title: 'Fuel stations', href: '/fleet/fuel-stations', icon: Fuel },
    { title: 'EV charging stations', href: '/fleet/ev-charging-stations', icon: Battery },
    { title: 'Service schedules', href: '/fleet/service-schedules', icon: ClipboardList },
    { title: 'Work orders', href: '/fleet/work-orders', icon: Wrench },
    { title: 'Defects', href: '/fleet/defects', icon: AlertTriangle },
    { title: 'Compliance items', href: '/fleet/compliance-items', icon: FileCheck },
    { title: 'Insurance policies', href: '/fleet/insurance-policies', icon: FileText },
    { title: 'Incidents', href: '/fleet/incidents', icon: AlertTriangle },
    { title: 'Insurance claims', href: '/fleet/insurance-claims', icon: FileText },
    { title: 'AI job runs', href: '/fleet/ai-job-runs', icon: BarChart3 },
    { title: 'AI analysis results', href: '/fleet/ai-analysis-results', icon: BarChart3 },
    { title: 'Electrification plan', href: '/fleet/electrification-plan', icon: Battery },
    { title: 'Fleet optimization', href: '/fleet/fleet-optimization', icon: BarChart3 },
    { title: 'Workflow definitions', href: '/fleet/workflow-definitions', icon: GitBranch },
    { title: 'Workflow executions', href: '/fleet/workflow-executions', icon: GitBranch },
    { title: 'Alerts', href: '/fleet/alerts', icon: AlertTriangle },
    { title: 'Alert preferences', href: '/fleet/alert-preferences', icon: Settings },
    { title: 'Reports', href: '/fleet/reports', icon: FileText },
    { title: 'Report executions', href: '/fleet/report-executions', icon: FileText },
];
