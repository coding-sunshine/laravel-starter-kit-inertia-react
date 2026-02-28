<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ReportType: string
{
    case FleetUtilization = 'fleet_utilization';
    case FuelEfficiency = 'fuel_efficiency';
    case DriverPerformance = 'driver_performance';
    case MaintenanceCosts = 'maintenance_costs';
    case ComplianceStatus = 'compliance_status';
    case SafetyAnalysis = 'safety_analysis';
    case CostAnalysis = 'cost_analysis';
    case EnvironmentalImpact = 'environmental_impact';
    case Custom = 'custom';
}
