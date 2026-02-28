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
use App\Models\Fleet\Route as FleetRoute;
use App\Models\Fleet\ServiceSchedule;
use App\Models\Fleet\SustainabilityGoal;
use App\Models\Fleet\TachographDownload;
use App\Models\Fleet\TrainingCourse;
use App\Models\Fleet\TrainingEnrollment;
use App\Models\Fleet\TrainingSession;
use App\Models\Fleet\Trip;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\WorkOrder;
use App\Models\Fleet\WorkflowDefinition;
use App\Models\Fleet\WorkflowExecution;
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
            'reports' => Report::count(),
            'report_executions' => ReportExecution::count(),
            'alert_preferences' => AlertPreference::query()
                ->where('user_id', $request->user()->id)
                ->where('organization_id', \App\Services\TenantContext::id())
                ->count(),
        ];

        $recentWorkOrders = WorkOrder::query()->with('vehicle')->orderByDesc('created_at')->limit(5)->get();
        $recentDefects = Defect::query()->with('vehicle')->orderByDesc('reported_at')->limit(5)->get();
        $expiringCompliance = ComplianceItem::query()->whereIn('status', ['valid', 'expiring_soon'])->orderBy('expiry_date')->limit(5)->get();

        return Inertia::render('Fleet/Dashboard', [
            'counts' => $counts,
            'recentWorkOrders' => $recentWorkOrders,
            'recentDefects' => $recentDefects,
            'expiringCompliance' => $expiringCompliance,
        ]);
    }
}
