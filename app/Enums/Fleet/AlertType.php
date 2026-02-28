<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum AlertType: string
{
    case ComplianceExpiry = 'compliance_expiry';
    case MaintenanceDue = 'maintenance_due';
    case DefectReported = 'defect_reported';
    case IncidentReported = 'incident_reported';
    case BehaviorViolation = 'behavior_violation';
    case FuelAnomaly = 'fuel_anomaly';
    case CostThreshold = 'cost_threshold';
    case GeofenceViolation = 'geofence_violation';
    case SpeedViolation = 'speed_violation';
    case WorkingTimeViolation = 'working_time_violation';
    case SystemError = 'system_error';
}
