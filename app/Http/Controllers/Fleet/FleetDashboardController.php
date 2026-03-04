<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Actions\Fleet\ComputeFleetHealthScoreAction;
use App\Actions\Fleet\GetFleetDashboardChartDataAction;
use App\Actions\Fleet\GetFleetDashboardSummaryAction;
use App\Http\Controllers\Controller;
use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\AiJobRun;
use App\Models\Fleet\Alert;
use App\Models\Fleet\AlertPreference;
use App\Models\Fleet\ApiIntegration;
use App\Models\Fleet\ApiLog;
use App\Models\Fleet\AxleLoadReading;
use App\Models\Fleet\BehaviorEvent;
use App\Models\Fleet\CarbonTarget;
use App\Models\Fleet\ComplianceItem;
use App\Models\Fleet\Contractor;
use App\Models\Fleet\ContractorCompliance;
use App\Models\Fleet\ContractorInvoice;
use App\Models\Fleet\CostAllocation;
use App\Models\Fleet\DashcamClip;
use App\Models\Fleet\DataMigrationRun;
use App\Models\Fleet\Defect;
use App\Models\Fleet\Driver;
use App\Models\Fleet\DriverCoachingPlan;
use App\Models\Fleet\DriverQualification;
use App\Models\Fleet\DriverVehicleAssignment;
use App\Models\Fleet\DriverWellnessRecord;
use App\Models\Fleet\DriverWorkingTime;
use App\Models\Fleet\ElockEvent;
use App\Models\Fleet\EmissionsRecord;
use App\Models\Fleet\EvBatteryData;
use App\Models\Fleet\EvChargingSession;
use App\Models\Fleet\Fine;
use App\Models\Fleet\FuelCard;
use App\Models\Fleet\FuelTransaction;
use App\Models\Fleet\Geofence;
use App\Models\Fleet\GeofenceEvent;
use App\Models\Fleet\GreyFleetVehicle;
use App\Models\Fleet\Incident;
use App\Models\Fleet\InsuranceClaim;
use App\Models\Fleet\InsurancePolicy;
use App\Models\Fleet\MileageClaim;
use App\Models\Fleet\ParkingAllocation;
use App\Models\Fleet\PartsInventory;
use App\Models\Fleet\PartsSupplier;
use App\Models\Fleet\PermitToWork;
use App\Models\Fleet\PoolVehicleBooking;
use App\Models\Fleet\PpeAssignment;
use App\Models\Fleet\Report;
use App\Models\Fleet\ReportExecution;
use App\Models\Fleet\RiskAssessment;
use App\Models\Fleet\Route as FleetRoute;
use App\Models\Fleet\SafetyObservation;
use App\Models\Fleet\SafetyPolicyAcknowledgment;
use App\Models\Fleet\ServiceSchedule;
use App\Models\Fleet\SustainabilityGoal;
use App\Models\Fleet\TachographCalibration;
use App\Models\Fleet\TachographDownload;
use App\Models\Fleet\ToolboxTalk;
use App\Models\Fleet\TrainingCourse;
use App\Models\Fleet\TrainingEnrollment;
use App\Models\Fleet\TrainingSession;
use App\Models\Fleet\Trip;
use App\Models\Fleet\TyreInventory;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleCheck;
use App\Models\Fleet\VehicleCheckTemplate;
use App\Models\Fleet\VehicleDisc;
use App\Models\Fleet\VehicleLease;
use App\Models\Fleet\VehicleRecall;
use App\Models\Fleet\VehicleTyre;
use App\Models\Fleet\WarrantyClaim;
use App\Models\Fleet\WorkflowDefinition;
use App\Models\Fleet\WorkflowExecution;
use App\Models\Fleet\WorkOrder;
use App\Models\Fleet\WorkshopBay;
use App\Services\Fleet\FleetInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FleetDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $counts = [
            'vehicles' => Vehicle::query()->count(),
            'drivers' => Driver::query()->count(),
            'driver_vehicle_assignments' => DriverVehicleAssignment::query()->count(),
            'routes' => FleetRoute::query()->count(),
            'trips' => Trip::query()->count(),
            'fuel_cards' => FuelCard::query()->count(),
            'fuel_transactions' => FuelTransaction::query()->count(),
            'service_schedules' => ServiceSchedule::query()->count(),
            'work_orders' => WorkOrder::query()->count(),
            'defects' => Defect::query()->count(),
            'compliance_items' => ComplianceItem::query()->count(),
            'driver_working_time' => DriverWorkingTime::query()->count(),
            'tachograph_downloads' => TachographDownload::query()->count(),
            'behavior_events' => BehaviorEvent::query()->count(),
            'geofence_events' => GeofenceEvent::query()->count(),
            'emissions_records' => EmissionsRecord::query()->count(),
            'carbon_targets' => CarbonTarget::query()->count(),
            'sustainability_goals' => SustainabilityGoal::query()->count(),
            'ai_analysis_results' => AiAnalysisResult::query()->count(),
            'ai_job_runs' => AiJobRun::query()->count(),
            'insurance_policies' => InsurancePolicy::query()->count(),
            'incidents' => Incident::query()->count(),
            'insurance_claims' => InsuranceClaim::query()->count(),
            'workflow_definitions' => WorkflowDefinition::query()->count(),
            'workflow_executions' => WorkflowExecution::query()->count(),
            'ev_charging_sessions' => EvChargingSession::query()->count(),
            'ev_battery_data' => EvBatteryData::query()->whereIn('vehicle_id', Vehicle::query()->pluck('id'))->count(),
            'training_courses' => TrainingCourse::query()->count(),
            'training_sessions' => TrainingSession::query()->count(),
            'driver_qualifications' => DriverQualification::query()->count(),
            'training_enrollments' => TrainingEnrollment::query()->count(),
            'cost_allocations' => CostAllocation::query()->count(),
            'alerts' => Alert::query()->count(),
            'alerts_open' => Alert::query()->where('status', 'active')->count(),
            'compliance_due_soon' => ComplianceItem::query()->where('status', 'expiring_soon')->count(),
            'reports' => Report::query()->count(),
            'report_executions' => ReportExecution::query()->count(),
            'alert_preferences' => AlertPreference::query()
                ->where('user_id', $request->user()->id)
                ->where('organization_id', \App\Services\TenantContext::id())
                ->count(),
            'api_integrations' => ApiIntegration::query()->count(),
            'api_logs' => ApiLog::query()->count(),
            'dashcam_clips' => DashcamClip::query()->count(),
            'workshop_bays' => WorkshopBay::query()->count(),
            'parts_inventory' => PartsInventory::query()->count(),
            'parts_suppliers' => PartsSupplier::query()->count(),
            'tyre_inventory' => TyreInventory::query()->count(),
            'vehicle_tyres' => VehicleTyre::query()->whereIn('vehicle_id', Vehicle::query()->pluck('id'))->count(),
            'grey_fleet_vehicles' => GreyFleetVehicle::query()->count(),
            'mileage_claims' => MileageClaim::query()->count(),
            'pool_vehicle_bookings' => PoolVehicleBooking::query()->count(),
            'contractors' => Contractor::query()->count(),
            'contractor_compliance' => ContractorCompliance::query()->count(),
            'contractor_invoices' => ContractorInvoice::query()->count(),
            'driver_wellness_records' => DriverWellnessRecord::query()->count(),
            'driver_coaching_plans' => DriverCoachingPlan::query()->count(),
            'vehicle_check_templates' => VehicleCheckTemplate::query()->count(),
            'vehicle_checks' => VehicleCheck::query()->count(),
            'risk_assessments' => RiskAssessment::query()->count(),
            'vehicle_discs' => VehicleDisc::query()->count(),
            'tachograph_calibrations' => TachographCalibration::query()->count(),
            'safety_policy_acknowledgments' => SafetyPolicyAcknowledgment::query()->count(),
            'permit_to_work' => PermitToWork::query()->count(),
            'ppe_assignments' => PpeAssignment::query()->count(),
            'safety_observations' => SafetyObservation::query()->count(),
            'toolbox_talks' => ToolboxTalk::query()->count(),
            'todays_vehicle_checks' => VehicleCheck::query()->whereDate('check_date', now()->toDateString())->count(),
            'fines' => Fine::query()->count(),
            'vehicle_leases' => VehicleLease::query()->count(),
            'vehicle_recalls' => VehicleRecall::query()->count(),
            'warranty_claims' => WarrantyClaim::query()->count(),
            'parking_allocations' => ParkingAllocation::query()->count(),
            'e_lock_events' => ElockEvent::query()->count(),
            'axle_load_readings' => AxleLoadReading::query()->count(),
            'data_migration_runs' => DataMigrationRun::query()->where('organization_id', \App\Services\TenantContext::id())->count(),
        ];

        $recentWorkOrders = WorkOrder::query()->with('vehicle')->latest()->limit(5)->get();
        $recentDefects = Defect::query()->with('vehicle')->latest('reported_at')->limit(5)->get();
        $expiringCompliance = ComplianceItem::query()->whereIn('status', ['valid', 'expiring_soon'])->oldest('expiry_date')->limit(5)->get();
        $complianceAtRisk = AiAnalysisResult::query()
            ->where('analysis_type', 'compliance_prediction')
            ->where('entity_type', 'organization')->latest()
            ->first();

        // Chart date range: 7, 14, or 30 days (default 14)
        $chartDays = (int) $request->input('chart_days', 14);
        $chartDays = in_array($chartDays, [7, 14, 30], true) ? $chartDays : 14;

        // Chart: trips per day
        $tripsOverTime = [];
        for ($i = $chartDays - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $tripsOverTime[] = [
                'date' => $date,
                'label' => now()->subDays($i)->format('M j'),
                'trips' => Trip::query()->whereDate('started_at', $date)->count(),
            ];
        }

        // Chart: work orders by status
        $workOrdersByStatus = WorkOrder::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();
        $chartWorkOrdersByStatus = $workOrdersByStatus->map(fn ($row): array => [
            'name' => ucfirst(str_replace('_', ' ', $row->status)),
            'value' => (int) $row->count,
        ])->values()->all();
        if (empty($chartWorkOrdersByStatus)) {
            $chartWorkOrdersByStatus = [['name' => 'No orders', 'value' => 0]];
        }

        // Chart: work orders per day
        $workOrdersOverTime = [];
        for ($i = $chartDays - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $workOrdersOverTime[] = [
                'date' => $date,
                'label' => now()->subDays($i)->format('M j'),
                'work_orders' => WorkOrder::query()->whereDate('created_at', $date)->count(),
            ];
        }

        $insightsService = resolve(FleetInsightsService::class);
        $orgId = \App\Services\TenantContext::id();
        $insights = Inertia::defer(fn () => $orgId !== null ? $insightsService->forOrganization($orgId) : []);
        $fleetAiSummary = Inertia::defer(fn () => $orgId !== null ? resolve(GetFleetDashboardSummaryAction::class)->handle($orgId, $counts) : null);
        $fleetHealth = $orgId !== null ? resolve(ComputeFleetHealthScoreAction::class)->handle($orgId) : null;

        $suggestedActions = $this->buildSuggestedActions($counts, $recentDefects, $recentWorkOrders);

        $recentAnomalies = AiAnalysisResult::query()
            ->whereIn('analysis_type', ['cost_optimization', 'risk_assessment'])
            ->where('created_at', '>=', now()->subDays(7))->latest()
            ->limit(5)
            ->get(['id', 'analysis_type', 'primary_finding', 'priority', 'created_at']);

        $chartData = Inertia::defer(fn () => resolve(GetFleetDashboardChartDataAction::class)->handle());

        // Vehicles with a mappable position: current_lat/lng or home location with lat/lng
        $mapVehicles = Vehicle::query()
            ->with('homeLocation:id,lat,lng')
            ->get(['id', 'registration', 'current_lat', 'current_lng', 'home_location_id'])
            ->map(function (Vehicle $v): ?array {
                $lat = $v->current_lat !== null && $v->current_lng !== null
                    ? (float) $v->current_lat
                    : null;
                $lng = $v->current_lat !== null && $v->current_lng !== null
                    ? (float) $v->current_lng
                    : null;
                $source = 'current';
                if ($lat === null && $v->homeLocation?->lat !== null && $v->homeLocation?->lng !== null) {
                    $lat = (float) $v->homeLocation->lat;
                    $lng = (float) $v->homeLocation->lng;
                    $source = 'home';
                }
                if ($lat === null || $lng === null) {
                    return null;
                }

                return [
                    'id' => $v->id,
                    'registration' => $v->registration,
                    'lat' => $lat,
                    'lng' => $lng,
                    'source' => $source,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return Inertia::render('Fleet/Dashboard', [
            'counts' => $counts,
            'chartDays' => $chartDays,
            'suggestedActions' => $suggestedActions,
            'chartTripsOverTime' => $tripsOverTime,
            'chartWorkOrdersByStatus' => array_values($chartWorkOrdersByStatus),
            'chartWorkOrdersOverTime' => $workOrdersOverTime,
            'recentWorkOrders' => $recentWorkOrders,
            'recentDefects' => $recentDefects,
            'expiringCompliance' => $expiringCompliance,
            'complianceAtRisk' => $complianceAtRisk,
            'aiJobRunsUrl' => route('fleet.ai-job-runs.index'),
            'insights' => $insights,
            'fleet_ai_summary' => $fleetAiSummary,
            'fleet_health_score' => $fleetHealth !== null ? $fleetHealth['score'] : null,
            'fleet_health_breakdown' => $fleetHealth !== null ? $fleetHealth['breakdown'] : null,
            'mapVehicles' => $mapVehicles,
            'mapGeofences' => Geofence::query()
                ->where('is_active', true)
                ->whereNotNull('polygon_coordinates')
                ->get()
                ->map(fn (Geofence $g): array => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'paths' => collect($g->polygon_coordinates ?? [])->map(fn ($p): array => [
                        'lat' => (float) (is_array($p) ? ($p['lat'] ?? $p[0] ?? 0) : 0),
                        'lng' => (float) (is_array($p) ? ($p['lng'] ?? $p[1] ?? 0) : 0),
                    ])->values()->all(),
                ])
                ->filter(fn (array $g): bool => count($g['paths']) >= 3)
                ->values()
                ->all(),
            'mapPolylines' => Trip::query()
                ->with(['waypoints' => fn ($q) => $q->orderBy('sequence')])
                ->where('started_at', '>=', now()->subDays(3))
                ->latest('started_at')
                ->limit(5)
                ->get()
                ->map(function ($trip): ?array {
                    $path = $trip->waypoints
                        ->sortBy('sequence')
                        ->map(fn ($w): array => ['lat' => (float) $w->lat, 'lng' => (float) $w->lng])
                        ->values()
                        ->all();

                    return count($path) >= 2 ? ['trip_id' => $trip->id, 'path' => $path] : null;
                })
                ->filter()
                ->values()
                ->all(),
            'recentAnomalies' => $recentAnomalies,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Live vehicle positions for map polling (e.g. every 10–30s). Returns same shape as mapVehicles.
     */
    public function vehiclePositions(Request $request): JsonResponse
    {
        $mapVehicles = Vehicle::query()
            ->with('homeLocation:id,lat,lng')
            ->get(['id', 'registration', 'current_lat', 'current_lng', 'home_location_id'])
            ->map(function (Vehicle $v): ?array {
                $lat = $v->current_lat !== null && $v->current_lng !== null
                    ? (float) $v->current_lat
                    : null;
                $lng = $v->current_lat !== null && $v->current_lng !== null
                    ? (float) $v->current_lng
                    : null;
                $source = 'current';
                if ($lat === null && $v->homeLocation?->lat !== null && $v->homeLocation?->lng !== null) {
                    $lat = (float) $v->homeLocation->lat;
                    $lng = (float) $v->homeLocation->lng;
                    $source = 'home';
                }
                if ($lat === null || $lng === null) {
                    return null;
                }

                return [
                    'id' => $v->id,
                    'registration' => $v->registration,
                    'lat' => $lat,
                    'lng' => $lng,
                    'source' => $source,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'vehicles' => $mapVehicles,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, int>  $counts
     * @param  \Illuminate\Database\Eloquent\Collection<int, Defect>  $recentDefects
     * @param  \Illuminate\Database\Eloquent\Collection<int, WorkOrder>  $recentWorkOrders
     * @return array<int, array{label: string, href: string, icon: string}>
     */
    private function buildSuggestedActions(
        array $counts,
        $recentDefects,
        $recentWorkOrders,
    ): array {
        $actions = [];

        $complianceDue = $counts['compliance_due_soon'] ?? 0;
        if ($complianceDue > 0) {
            $actions[] = [
                'label' => $complianceDue === 1
                    ? '1 compliance item due soon – review'
                    : "{$complianceDue} compliance items due soon – review",
                'href' => '/fleet/compliance',
                'icon' => 'clipboard-check',
            ];
        }

        $openDefects = $recentDefects->whereIn('status', ['open', 'pending', 'in_progress'])->count();
        if ($openDefects > 0) {
            $actions[] = [
                'label' => $openDefects === 1
                    ? '1 open defect – review'
                    : "{$openDefects} open defects – review",
                'href' => '/fleet/defects',
                'icon' => 'alert-triangle',
            ];
        }

        $openWorkOrders = $recentWorkOrders->whereIn('status', ['draft', 'pending', 'in_progress', 'scheduled'])->count();
        if ($openWorkOrders > 0) {
            $actions[] = [
                'label' => $openWorkOrders === 1
                    ? '1 work order in progress'
                    : "{$openWorkOrders} work orders in progress",
                'href' => '/fleet/work-orders',
                'icon' => 'wrench',
            ];
        }

        if ($counts['vehicles'] === 0) {
            $actions[] = [
                'label' => 'Add your first vehicle',
                'href' => '/fleet/vehicles/create',
                'icon' => 'truck',
            ];
        } elseif ($counts['drivers'] === 0) {
            $actions[] = [
                'label' => 'Add your first driver',
                'href' => '/fleet/drivers/create',
                'icon' => 'users',
            ];
        }

        return array_slice($actions, 0, 4);
    }
}
