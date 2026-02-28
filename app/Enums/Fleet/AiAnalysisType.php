<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum AiAnalysisType: string
{
    case FraudDetection = 'fraud_detection';
    case PredictiveMaintenance = 'predictive_maintenance';
    case RouteOptimization = 'route_optimization';
    case DriverCoaching = 'driver_coaching';
    case CostOptimization = 'cost_optimization';
    case CompliancePrediction = 'compliance_prediction';
    case RiskAssessment = 'risk_assessment';
    case FuelEfficiency = 'fuel_efficiency';
    case SafetyScoring = 'safety_scoring';
}
