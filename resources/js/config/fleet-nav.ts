import type { LucideIcon } from 'lucide-react';
import {
    AlertTriangle,
    BarChart3,
    Battery,
    BotMessageSquare,
    BrainCircuit,
    Car,
    ClipboardList,
    FileCheck,
    FileText,
    Fuel,
    LayoutGrid,
    MapPin,
    Route,
    Shield,
    Truck,
    Users,
    Wrench,
    Zap,
} from 'lucide-react';

export interface FleetNavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
}

export interface FleetNavSection {
    label: string;
    icon?: LucideIcon;
    items: FleetNavItem[];
    submenu?: FleetNavItem[];
}

/** Dashboard link shown first in fleet-only sidebar. */
export const fleetDashboardItem: FleetNavItem = {
    title: 'Dashboard',
    href: '/fleet',
    icon: LayoutGrid,
};

/** Fleet sidebar in 6 logical groups (Dashboard is separate). */
export const fleetNavSections: FleetNavSection[] = [
    {
        label: 'AI & Intelligence',
        icon: BrainCircuit,
        items: [
            { title: 'Assistant', href: '/fleet/assistant', icon: BotMessageSquare },
            { title: 'AI Insights', href: '/fleet/ai-analysis-results', icon: BrainCircuit },
            { title: 'Electrification', href: '/fleet/electrification-plan', icon: Zap },
            { title: 'Optimization', href: '/fleet/fleet-optimization', icon: BarChart3 },
        ],
        submenu: [
            { title: 'AI Job Runs', href: '/fleet/ai-job-runs' },
        ],
    },
    {
        label: 'Fleet Operations',
        icon: Truck,
        items: [
            { title: 'Vehicles', href: '/fleet/vehicles', icon: Car },
            { title: 'Drivers', href: '/fleet/drivers', icon: Users },
            { title: 'Routes', href: '/fleet/routes', icon: Route },
            { title: 'Trips', href: '/fleet/trips', icon: Route },
        ],
        submenu: [
            { title: 'Driver-Vehicle Assignments', href: '/fleet/driver-vehicle-assignments' },
            { title: 'Grey Fleet Vehicles', href: '/fleet/grey-fleet-vehicles' },
            { title: 'Pool Vehicle Bookings', href: '/fleet/pool-vehicle-bookings' },
            { title: 'Trailers', href: '/fleet/trailers' },
        ],
    },
    {
        label: 'Maintenance',
        icon: Wrench,
        items: [
            { title: 'Work Orders', href: '/fleet/work-orders', icon: Wrench },
            { title: 'Defects', href: '/fleet/defects', icon: AlertTriangle },
            { title: 'Service Schedules', href: '/fleet/service-schedules', icon: ClipboardList },
        ],
        submenu: [
            { title: 'Workshop Bays', href: '/fleet/workshop-bays' },
            { title: 'Parts Inventory', href: '/fleet/parts-inventory' },
            { title: 'Parts Suppliers', href: '/fleet/parts-suppliers' },
            { title: 'Warranty Claims', href: '/fleet/warranty-claims' },
            { title: 'Garages', href: '/fleet/garages' },
        ],
    },
    {
        label: 'Fuel & Energy',
        icon: Fuel,
        items: [
            { title: 'Fuel Transactions', href: '/fleet/fuel-transactions', icon: Fuel },
            { title: 'EV Charging', href: '/fleet/ev-charging-sessions', icon: Battery },
        ],
        submenu: [
            { title: 'Fuel Cards', href: '/fleet/fuel-cards' },
            { title: 'Fuel Stations', href: '/fleet/fuel-stations' },
            { title: 'EV Charging Stations', href: '/fleet/ev-charging-stations' },
            { title: 'EV Battery Data', href: '/fleet/ev-battery-data' },
            { title: 'Carbon Targets', href: '/fleet/carbon-targets' },
            { title: 'Emissions Records', href: '/fleet/emissions-records' },
            { title: 'Sustainability Goals', href: '/fleet/sustainability-goals' },
        ],
    },
    {
        label: 'Compliance & Safety',
        icon: Shield,
        items: [
            { title: 'Compliance', href: '/fleet/compliance-items', icon: FileCheck },
            { title: 'Incidents', href: '/fleet/incidents', icon: AlertTriangle },
            { title: 'Insurance', href: '/fleet/insurance-policies', icon: FileText },
            { title: 'Alerts', href: '/fleet/alerts', icon: AlertTriangle },
        ],
        submenu: [
            { title: 'Insurance Claims', href: '/fleet/insurance-claims' },
            { title: 'Fines', href: '/fleet/fines' },
            { title: 'Driver Qualifications', href: '/fleet/driver-qualifications' },
            { title: 'Training Courses', href: '/fleet/training-courses' },
            { title: 'Training Sessions', href: '/fleet/training-sessions' },
            { title: 'Training Enrollments', href: '/fleet/training-enrollments' },
            { title: 'Risk Assessments', href: '/fleet/risk-assessments' },
            { title: 'Safety Observations', href: '/fleet/safety-observations' },
            { title: 'Toolbox Talks', href: '/fleet/toolbox-talks' },
            { title: 'Permits to Work', href: '/fleet/permit-to-work' },
            { title: 'PPE Assignments', href: '/fleet/ppe-assignments' },
            { title: 'Driver Coaching Plans', href: '/fleet/driver-coaching-plans' },
            { title: 'Driver Wellness', href: '/fleet/driver-wellness-records' },
            { title: 'Driver Working Time', href: '/fleet/driver-working-time' },
            { title: 'Tachograph Calibrations', href: '/fleet/tachograph-calibrations' },
            { title: 'Tachograph Downloads', href: '/fleet/tachograph-downloads' },
            { title: 'Vehicle Checks', href: '/fleet/vehicle-checks' },
            { title: 'Vehicle Check Templates', href: '/fleet/vehicle-check-templates' },
            { title: 'Vehicle Discs', href: '/fleet/vehicle-discs' },
            { title: 'Vehicle Tyres', href: '/fleet/vehicle-tyres' },
            { title: 'Tyre Inventory', href: '/fleet/tyre-inventory' },
            { title: 'Axle Load Readings', href: '/fleet/axle-load-readings' },
            { title: 'Operator Licences', href: '/fleet/operator-licences' },
            { title: 'Safety Policy Acknowledgments', href: '/fleet/safety-policy-acknowledgments' },
            { title: 'Dashcam Clips', href: '/fleet/dashcam-clips' },
            { title: 'E-Lock Events', href: '/fleet/e-lock-events' },
            { title: 'Behaviour Events', href: '/fleet/behavior-events' },
            { title: 'Geofences', href: '/fleet/geofences' },
            { title: 'Geofence Events', href: '/fleet/geofence-events' },
            { title: 'Telematics Devices', href: '/fleet/telematics-devices' },
            { title: 'Alert Preferences', href: '/fleet/alert-preferences' },
        ],
    },
    {
        label: 'Reports & Admin',
        icon: FileText,
        items: [
            { title: 'Reports', href: '/fleet/reports', icon: FileText },
            { title: 'Locations', href: '/fleet/locations', icon: MapPin },
        ],
        submenu: [
            { title: 'Report Executions', href: '/fleet/report-executions' },
            { title: 'Cost Centers', href: '/fleet/cost-centers' },
            { title: 'Cost Allocations', href: '/fleet/cost-allocations' },
            { title: 'Parking Allocations', href: '/fleet/parking-allocations' },
            { title: 'API Integrations', href: '/fleet/api-integrations' },
            { title: 'API Logs', href: '/fleet/api-logs' },
            { title: 'Data Migration Runs', href: '/fleet/data-migration-runs' },
            { title: 'Contractors', href: '/fleet/contractors' },
            { title: 'Contractor Compliance', href: '/fleet/contractor-compliance' },
            { title: 'Contractor Invoices', href: '/fleet/contractor-invoices' },
            { title: 'Mileage Claims', href: '/fleet/mileage-claims' },
            { title: 'Vehicle Leases', href: '/fleet/vehicle-leases' },
            { title: 'Vehicle Recalls', href: '/fleet/vehicle-recalls' },
            { title: 'Workflow Definitions', href: '/fleet/workflow-definitions' },
            { title: 'Workflow Executions', href: '/fleet/workflow-executions' },
        ],
    },
];

/** Flat list of all fleet nav items for backward compatibility and search. */
export const fleetNavItems: FleetNavItem[] = [
    fleetDashboardItem,
    ...fleetNavSections.flatMap((s) => [...s.items, ...(s.submenu ?? [])]),
];
