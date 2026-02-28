import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    Battery,
    Bell,
    Calendar,
    ClipboardList,
    Clock,
    CreditCard,
    Cpu,
    FileDown,
    FileText,
    Flame,
    Fuel,
    GitBranch,
    GraduationCap,
    MapPin,
    Route,
    ShieldCheck,
    Truck,
    Users,
    Wrench,
    Package,
    CircleDot,
    Car,
    FileCheck,
    UserCog,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Counts {
    vehicles: number;
    drivers: number;
    driver_vehicle_assignments: number;
    routes: number;
    trips: number;
    fuel_cards: number;
    fuel_transactions: number;
    service_schedules: number;
    work_orders: number;
    defects: number;
    compliance_items: number;
    driver_working_time: number;
    tachograph_downloads: number;
    behavior_events: number;
    geofence_events: number;
    emissions_records?: number;
    carbon_targets?: number;
    sustainability_goals?: number;
    ai_analysis_results?: number;
    ai_job_runs?: number;
    insurance_policies?: number;
    incidents?: number;
    insurance_claims?: number;
    workflow_definitions?: number;
    workflow_executions?: number;
    ev_charging_sessions?: number;
    ev_battery_data?: number;
    training_courses?: number;
    training_sessions?: number;
    driver_qualifications?: number;
    training_enrollments?: number;
    cost_allocations?: number;
    alerts?: number;
    reports?: number;
    report_executions?: number;
    alert_preferences?: number;
    api_integrations?: number;
    api_logs?: number;
    dashcam_clips?: number;
    workshop_bays?: number;
    parts_inventory?: number;
    parts_suppliers?: number;
    tyre_inventory?: number;
    vehicle_tyres?: number;
    grey_fleet_vehicles?: number;
    mileage_claims?: number;
    pool_vehicle_bookings?: number;
    contractors?: number;
    contractor_compliance?: number;
    contractor_invoices?: number;
}
interface WorkOrderRow { id: number; work_order_number: string; title: string; status: string; vehicle?: { id: number; registration: string }; }
interface DefectRow { id: number; defect_number: string; title: string; severity: string; vehicle?: { id: number; registration: string }; }
interface ComplianceRow { id: number; title: string; expiry_date: string; status: string; entity_type: string; entity_id: number; }
interface Props {
    counts: Counts;
    recentWorkOrders: WorkOrderRow[];
    recentDefects: DefectRow[];
    expiringCompliance: ComplianceRow[];
}

function StatCard({
    title,
    count,
    href,
    icon: Icon,
}: { title: string; count: number; href: string; icon: React.ComponentType<{ className?: string }> }) {
    return (
        <Link href={href} className="block">
            <Card className="transition-colors hover:bg-muted/50">
                <CardContent className="flex items-center gap-4 p-4">
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                        <Icon className="size-6 text-primary" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-medium">{title}</p>
                        <p className="text-2xl font-semibold tabular-nums">{count}</p>
                    </div>
                </CardContent>
            </Card>
        </Link>
    );
}

export default function FleetDashboard({ counts, recentWorkOrders, recentDefects, expiringCompliance }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-8 rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Fleet dashboard</h1>
                    <p className="text-muted-foreground">Overview and quick access to all fleet areas.</p>
                </div>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Core</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Vehicles" count={counts.vehicles} href="/fleet/vehicles" icon={Truck} />
                        <StatCard title="Drivers" count={counts.drivers} href="/fleet/drivers" icon={Users} />
                        <StatCard title="Driver–vehicle assignments" count={counts.driver_vehicle_assignments} href="/fleet/driver-vehicle-assignments" icon={Users} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Trips & routes</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Routes" count={counts.routes} href="/fleet/routes" icon={Route} />
                        <StatCard title="Trips" count={counts.trips} href="/fleet/trips" icon={MapPin} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Fuel</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Fuel cards" count={counts.fuel_cards} href="/fleet/fuel-cards" icon={CreditCard} />
                        <StatCard title="Fuel transactions" count={counts.fuel_transactions} href="/fleet/fuel-transactions" icon={Fuel} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Maintenance</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Service schedules" count={counts.service_schedules} href="/fleet/service-schedules" icon={Calendar} />
                        <StatCard title="Work orders" count={counts.work_orders} href="/fleet/work-orders" icon={ClipboardList} />
                        <StatCard title="Defects" count={counts.defects} href="/fleet/defects" icon={AlertTriangle} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Compliance & working time</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Compliance items" count={counts.compliance_items} href="/fleet/compliance-items" icon={ShieldCheck} />
                        <StatCard title="Driver working time" count={counts.driver_working_time} href="/fleet/driver-working-time" icon={Clock} />
                        <StatCard title="Tachograph downloads" count={counts.tachograph_downloads} href="/fleet/tachograph-downloads" icon={FileDown} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Telematics & events</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Behavior events" count={counts.behavior_events} href="/fleet/behavior-events" icon={AlertTriangle} />
                        <StatCard title="Geofence events" count={counts.geofence_events} href="/fleet/geofence-events" icon={MapPin} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Carbon, sustainability & AI</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Emissions records" count={counts.emissions_records ?? 0} href="/fleet/emissions-records" icon={Flame} />
                        <StatCard title="Carbon targets" count={counts.carbon_targets ?? 0} href="/fleet/carbon-targets" icon={Route} />
                        <StatCard title="Sustainability goals" count={counts.sustainability_goals ?? 0} href="/fleet/sustainability-goals" icon={Calendar} />
                        <StatCard title="AI analysis results" count={counts.ai_analysis_results ?? 0} href="/fleet/ai-analysis-results" icon={Cpu} />
                        <StatCard title="AI job runs" count={counts.ai_job_runs ?? 0} href="/fleet/ai-job-runs" icon={Cpu} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Insurance & incidents</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Insurance policies" count={counts.insurance_policies ?? 0} href="/fleet/insurance-policies" icon={ShieldCheck} />
                        <StatCard title="Incidents" count={counts.incidents ?? 0} href="/fleet/incidents" icon={AlertTriangle} />
                        <StatCard title="Insurance claims" count={counts.insurance_claims ?? 0} href="/fleet/insurance-claims" icon={CreditCard} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Workflows</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Workflow definitions" count={counts.workflow_definitions ?? 0} href="/fleet/workflow-definitions" icon={GitBranch} />
                        <StatCard title="Workflow executions" count={counts.workflow_executions ?? 0} href="/fleet/workflow-executions" icon={GitBranch} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">EV, training & costs</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="EV charging sessions" count={counts.ev_charging_sessions ?? 0} href="/fleet/ev-charging-sessions" icon={Battery} />
                        <StatCard title="EV battery data" count={counts.ev_battery_data ?? 0} href="/fleet/ev-battery-data" icon={Battery} />
                        <StatCard title="Training courses" count={counts.training_courses ?? 0} href="/fleet/training-courses" icon={GraduationCap} />
                        <StatCard title="Training sessions" count={counts.training_sessions ?? 0} href="/fleet/training-sessions" icon={Calendar} />
                        <StatCard title="Driver qualifications" count={counts.driver_qualifications ?? 0} href="/fleet/driver-qualifications" icon={ShieldCheck} />
                        <StatCard title="Training enrollments" count={counts.training_enrollments ?? 0} href="/fleet/training-enrollments" icon={GraduationCap} />
                        <StatCard title="Cost allocations" count={counts.cost_allocations ?? 0} href="/fleet/cost-allocations" icon={CreditCard} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Workshop, tyres & grey fleet</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="API integrations" count={counts.api_integrations ?? 0} href="/fleet/api-integrations" icon={Cpu} />
                        <StatCard title="API logs" count={counts.api_logs ?? 0} href="/fleet/api-logs" icon={FileText} />
                        <StatCard title="Dashcam clips" count={counts.dashcam_clips ?? 0} href="/fleet/dashcam-clips" icon={AlertTriangle} />
                        <StatCard title="Workshop bays" count={counts.workshop_bays ?? 0} href="/fleet/workshop-bays" icon={Wrench} />
                        <StatCard title="Parts inventory" count={counts.parts_inventory ?? 0} href="/fleet/parts-inventory" icon={Package} />
                        <StatCard title="Parts suppliers" count={counts.parts_suppliers ?? 0} href="/fleet/parts-suppliers" icon={Package} />
                        <StatCard title="Tyre inventory" count={counts.tyre_inventory ?? 0} href="/fleet/tyre-inventory" icon={CircleDot} />
                        <StatCard title="Vehicle tyres" count={counts.vehicle_tyres ?? 0} href="/fleet/vehicle-tyres" icon={CircleDot} />
                        <StatCard title="Grey fleet vehicles" count={counts.grey_fleet_vehicles ?? 0} href="/fleet/grey-fleet-vehicles" icon={Car} />
                        <StatCard title="Mileage claims" count={counts.mileage_claims ?? 0} href="/fleet/mileage-claims" icon={CreditCard} />
                        <StatCard title="Pool vehicle bookings" count={counts.pool_vehicle_bookings ?? 0} href="/fleet/pool-vehicle-bookings" icon={Car} />
                        <StatCard title="Contractors" count={counts.contractors ?? 0} href="/fleet/contractors" icon={UserCog} />
                        <StatCard title="Contractor compliance" count={counts.contractor_compliance ?? 0} href="/fleet/contractor-compliance" icon={FileCheck} />
                        <StatCard title="Contractor invoices" count={counts.contractor_invoices ?? 0} href="/fleet/contractor-invoices" icon={FileText} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Alerts & reports</h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard title="Alerts" count={counts.alerts ?? 0} href="/fleet/alerts" icon={Bell} />
                        <StatCard title="Alert preferences" count={counts.alert_preferences ?? 0} href="/fleet/alert-preferences" icon={Bell} />
                        <StatCard title="Reports" count={counts.reports ?? 0} href="/fleet/reports" icon={FileText} />
                        <StatCard title="Report executions" count={counts.report_executions ?? 0} href="/fleet/report-executions" icon={FileText} />
                    </div>
                </section>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center justify-between text-base">
                                Recent work orders
                                <Link href="/fleet/work-orders" className="text-sm font-normal text-primary hover:underline">View all</Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentWorkOrders.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No work orders yet.</p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {recentWorkOrders.map((wo) => (
                                        <li key={wo.id} className="flex items-center justify-between border-b border-dashed pb-2 last:border-0">
                                            <Link href={`/fleet/work-orders/${wo.id}`} className="font-medium hover:underline">{wo.work_order_number}</Link>
                                            <span className="text-muted-foreground">{wo.vehicle?.registration ?? wo.status}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center justify-between text-base">
                                Recent defects
                                <Link href="/fleet/defects" className="text-sm font-normal text-primary hover:underline">View all</Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentDefects.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No defects reported.</p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {recentDefects.map((d) => (
                                        <li key={d.id} className="flex items-center justify-between border-b border-dashed pb-2 last:border-0">
                                            <Link href={`/fleet/defects/${d.id}`} className="font-medium hover:underline">{d.defect_number}</Link>
                                            <span className="text-muted-foreground">{d.severity}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center justify-between text-base">
                                Expiring compliance
                                <Link href="/fleet/compliance-items" className="text-sm font-normal text-primary hover:underline">View all</Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {expiringCompliance.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Nothing expiring soon.</p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {expiringCompliance.map((c) => (
                                        <li key={c.id} className="flex items-center justify-between border-b border-dashed pb-2 last:border-0">
                                            <Link href={`/fleet/compliance-items/${c.id}`} className="font-medium hover:underline">{c.title}</Link>
                                            <span className="text-muted-foreground">{new Date(c.expiry_date).toLocaleDateString()}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <section>
                    <h2 className="mb-3 text-lg font-medium">Setup & configuration</h2>
                    <div className="flex flex-wrap gap-2">
                        <Link href="/fleet/locations" className="rounded-lg border bg-card px-4 py-2 text-sm font-medium hover:bg-muted">Locations</Link>
                        <Link href="/fleet/cost-centers" className="rounded-lg border bg-card px-4 py-2 text-sm font-medium hover:bg-muted">Cost centers</Link>
                        <Link href="/fleet/garages" className="rounded-lg border bg-card px-4 py-2 text-sm font-medium hover:bg-muted">Garages</Link>
                        <Link href="/fleet/fuel-stations" className="rounded-lg border bg-card px-4 py-2 text-sm font-medium hover:bg-muted">Fuel stations</Link>
                        <Link href="/fleet/ev-charging-stations" className="rounded-lg border bg-card px-4 py-2 text-sm font-medium hover:bg-muted">EV charging stations</Link>
                        <Link href="/fleet/geofences" className="rounded-lg border bg-card px-4 py-2 text-sm font-medium hover:bg-muted">Geofences</Link>
                        <Link href="/fleet/trailers" className="rounded-lg border bg-card px-4 py-2 text-sm font-medium hover:bg-muted">Trailers</Link>
                        <Link href="/fleet/operator-licences" className="rounded-lg border bg-card px-4 py-2 text-sm font-medium hover:bg-muted">Operator licences</Link>
                        <Link href="/fleet/telematics-devices" className="rounded-lg border bg-card px-4 py-2 text-sm font-medium hover:bg-muted">Telematics devices</Link>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
