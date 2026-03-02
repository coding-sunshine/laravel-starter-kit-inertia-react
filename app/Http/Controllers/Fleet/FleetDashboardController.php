<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\AiJobRun;
use App\Models\Fleet\Alert;
use App\Models\Fleet\BehaviorEvent;
use App\Models\Fleet\CarbonTarget;
use App\Models\Fleet\ComplianceItem;
use App\Models\Fleet\CostAllocation;
use App\Models\Fleet\Defect;
use App\Models\Fleet\Driver;
use App\Models\Fleet\DriverQualification;
use App\Models\Fleet\DriverVehicleAssignment;
use App\Models\Fleet\DriverWorkingTime;
use App\Models\Fleet\EmissionsRecord;
use App\Models\Fleet\EvBatteryData;
use App\Models\Fleet\EvChargingSession;
use App\Models\Fleet\FuelCard;
use App\Models\Fleet\FuelTransaction;
use App\Models\Fleet\GeofenceEvent;
use App\Models\Fleet\Incident;
use App\Models\Fleet\InsuranceClaim;
use App\Models\Fleet\InsurancePolicy;
use App\Models\Fleet\Report;
use App\Models\Fleet\ReportExecution;
use App\Models\Fleet\AlertPreference;
use App\Models\Fleet\ApiIntegration;
use App\Models\Fleet\ApiLog;
use App\Models\Fleet\DashcamClip;
use App\Models\Fleet\WorkshopBay;
use App\Models\Fleet\PartsInventory;
use App\Models\Fleet\PartsSupplier;
use App\Models\Fleet\TyreInventory;
use App\Models\Fleet\VehicleTyre;
use App\Models\Fleet\GreyFleetVehicle;
use App\Models\Fleet\MileageClaim;
use App\Models\Fleet\PoolVehicleBooking;
use App\Models\Fleet\Contractor;
use App\Models\Fleet\ContractorCompliance;
use App\Models\Fleet\ContractorInvoice;
use App\Models\Fleet\DriverCoachingPlan;
use App\Models\Fleet\DriverWellnessRecord;
use App\Models\Fleet\PermitToWork;
use App\Models\Fleet\PpeAssignment;
use App\Models\Fleet\RiskAssessment;
use App\Models\Fleet\Route as FleetRoute;
use App\Models\Fleet\SafetyObservation;
use App\Models\Fleet\SafetyPolicyAcknowledgment;
use App\Models\Fleet\TachographCalibration;
use App\Models\Fleet\ToolboxTalk;
use App\Models\Fleet\VehicleCheck;
use App\Models\Fleet\VehicleCheckTemplate;
use App\Models\Fleet\VehicleDisc;
use App\Models\Fleet\ServiceSchedule;
use App\Models\Fleet\SustainabilityGoal;
use App\Models\Fleet\TachographDownload;
use App\Models\Fleet\TrainingCourse;
use App\Models\Fleet\TrainingEnrollment;
use App\Models\Fleet\TrainingSession;
use App\Models\Fleet\Trip;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\Fine;
use App\Models\Fleet\VehicleLease;
use App\Models\Fleet\VehicleRecall;
use App\Models\Fleet\WarrantyClaim;
use App\Models\Fleet\WorkOrder;
use App\Models\Fleet\ParkingAllocation;
use App\Models\Fleet\ElockEvent;
use App\Models\Fleet\AxleLoadReading;
use App\Models\Fleet\DataMigrationRun;
use App\Models\Fleet\WorkflowDefinition;
use App\Models\Fleet\WorkflowExecution;
use App\Services\Fleet\FleetInsightsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FleetDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $counts = [
            'vehicles' => Vehicle::count(),
            'drivers' => Driver::count(),
            'driver_vehicle_assignments' => DriverVehicleAssignment::count(),
            'routes' => FleetRoute::count(),
            'trips' => Trip::count(),
            'fuel_cards' => FuelCard::count(),
            'fuel_transactions' => FuelTransaction::count(),
            'service_schedules' => ServiceSchedule::count(),
            'work_orders' => WorkOrder::count(),
            'defects' => Defect::count(),
            'compliance_items' => ComplianceItem::count(),
            'driver_working_time' => DriverWorkingTime::count(),
            'tachograph_downloads' => TachographDownload::count(),
            'behavior_events' => BehaviorEvent::count(),
            'geofence_events' => GeofenceEvent::count(),
            'emissions_records' => EmissionsRecord::count(),
            'carbon_targets' => CarbonTarget::count(),
            'sustainability_goals' => SustainabilityGoal::count(),
            'ai_analysis_results' => AiAnalysisResult::count(),
            'ai_job_runs' => AiJobRun::count(),
            'insurance_policies' => InsurancePolicy::count(),
            'incidents' => Incident::count(),
            'insurance_claims' => InsuranceClaim::count(),
            'workflow_definitions' => WorkflowDefinition::count(),
            'workflow_executions' => WorkflowExecution::count(),
            'ev_charging_sessions' => EvChargingSession::count(),
            'ev_battery_data' => EvBatteryData::query()->whereIn('vehicle_id', Vehicle::pluck('id'))->count(),
            'training_courses' => TrainingCourse::count(),
            'training_sessions' => TrainingSession::count(),
            'driver_qualifications' => DriverQualification::count(),
            'training_enrollments' => TrainingEnrollment::count(),
            'cost_allocations' => CostAllocation::count(),
            'alerts' => Alert::count(),
            'alerts_open' => Alert::where('status', 'active')->count(),
            'compliance_due_soon' => ComplianceItem::query()->where('status', 'expiring_soon')->count(),
            'reports' => Report::count(),
            'report_executions' => ReportExecution::count(),
            'alert_preferences' => AlertPreference::query()
                ->where('user_id', $request->user()->id)
                ->where('organization_id', \App\Services\TenantContext::id())
                ->count(),
            'api_integrations' => ApiIntegration::count(),
            'api_logs' => ApiLog::count(),
            'dashcam_clips' => DashcamClip::count(),
            'workshop_bays' => WorkshopBay::count(),
            'parts_inventory' => PartsInventory::count(),
            'parts_suppliers' => PartsSupplier::count(),
            'tyre_inventory' => TyreInventory::count(),
            'vehicle_tyres' => VehicleTyre::query()->whereIn('vehicle_id', Vehicle::pluck('id'))->count(),
            'grey_fleet_vehicles' => GreyFleetVehicle::count(),
            'mileage_claims' => MileageClaim::count(),
            'pool_vehicle_bookings' => PoolVehicleBooking::count(),
            'contractors' => Contractor::count(),
            'contractor_compliance' => ContractorCompliance::count(),
            'contractor_invoices' => ContractorInvoice::count(),
            'driver_wellness_records' => DriverWellnessRecord::count(),
            'driver_coaching_plans' => DriverCoachingPlan::count(),
            'vehicle_check_templates' => VehicleCheckTemplate::count(),
            'vehicle_checks' => VehicleCheck::count(),
            'risk_assessments' => RiskAssessment::count(),
            'vehicle_discs' => VehicleDisc::count(),
            'tachograph_calibrations' => TachographCalibration::count(),
            'safety_policy_acknowledgments' => SafetyPolicyAcknowledgment::count(),
            'permit_to_work' => PermitToWork::count(),
            'ppe_assignments' => PpeAssignment::count(),
            'safety_observations' => SafetyObservation::count(),
            'toolbox_talks' => ToolboxTalk::count(),
            'todays_vehicle_checks' => VehicleCheck::query()->whereDate('check_date', now()->toDateString())->count(),
            'fines' => Fine::count(),
            'vehicle_leases' => VehicleLease::count(),
            'vehicle_recalls' => VehicleRecall::count(),
            'warranty_claims' => WarrantyClaim::count(),
            'parking_allocations' => ParkingAllocation::count(),
            'e_lock_events' => ElockEvent::count(),
            'axle_load_readings' => AxleLoadReading::count(),
            'data_migration_runs' => DataMigrationRun::query()->where('organization_id', \App\Services\TenantContext::id())->count(),
        ];

        $recentWorkOrders = WorkOrder::query()->with('vehicle')->orderByDesc('created_at')->limit(5)->get();
        $recentDefects = Defect::query()->with('vehicle')->orderByDesc('reported_at')->limit(5)->get();
        $expiringCompliance = ComplianceItem::query()->whereIn('status', ['valid', 'expiring_soon'])->orderBy('expiry_date')->limit(5)->get();
        $complianceAtRisk = AiAnalysisResult::query()
            ->where('analysis_type', 'compliance_prediction')
            ->where('entity_type', 'organization')
            ->orderByDesc('created_at')
            ->first();

        // Chart: trips per day (last 14 days)
        $days = 14;
        $tripsOverTime = [];
        for ($i = $days - 1; $i >= 0; $i--) {
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

        // Chart: work orders per day (last 14 days)
        $workOrdersOverTime = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $workOrdersOverTime[] = [
                'date' => $date,
                'label' => now()->subDays($i)->format('M j'),
                'work_orders' => WorkOrder::query()->whereDate('created_at', $date)->count(),
            ];
        }

        $insightsService = app(FleetInsightsService::class);
        $orgId = \App\Services\TenantContext::id();
        $insights = $orgId !== null ? $insightsService->forOrganization($orgId) : [];

        return Inertia::render('Fleet/Dashboard', [
            'counts' => $counts,
            'chartTripsOverTime' => $tripsOverTime,
            'chartWorkOrdersByStatus' => array_values($chartWorkOrdersByStatus),
            'chartWorkOrdersOverTime' => $workOrdersOverTime,
            'recentWorkOrders' => $recentWorkOrders,
            'recentDefects' => $recentDefects,
            'expiringCompliance' => $expiringCompliance,
            'complianceAtRisk' => $complianceAtRisk,
            'aiJobRunsUrl' => route('fleet.ai-job-runs.index'),
            'insights' => $insights,
        ]);
    }
}
