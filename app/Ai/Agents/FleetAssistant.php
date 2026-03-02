<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\Fleet\FleetDocumentSearch;
use App\Ai\Tools\Fleet\GetDriver;
use App\Ai\Tools\Fleet\GetRoute;
use App\Ai\Tools\Fleet\GetTrip;
use App\Ai\Tools\Fleet\GetVehicle;
use App\Ai\Tools\Fleet\GetWorkOrder;
use App\Ai\Tools\Fleet\ListAlerts;
use App\Ai\Tools\Fleet\ListComplianceItems;
use App\Ai\Tools\Fleet\ListDefects;
use App\Ai\Tools\Fleet\ListDrivers;
use App\Ai\Tools\Fleet\ListRoutes;
use App\Ai\Tools\Fleet\ListServiceSchedules;
use App\Ai\Tools\Fleet\ListTrips;
use App\Ai\Tools\Fleet\ListVehicles;
use App\Ai\Tools\Fleet\ListWorkOrders;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Promptable;
use Stringable;

final class FleetAssistant implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        public ?int $organizationId = null,
        public ?int $userId = null,
    ) {}

    public function instructions(): string|Stringable
    {
        return 'You are the Fleet Intelligence Assistant. Answer only using fleet data and the documents provided by your tools. '
            .'You can: search fleet documents (MOT, V5C, insurance, service history) with the document search tool—when you use it, always cite the source (document name or reference). '
            .'List tools: vehicles, drivers, trips, work orders, compliance items, alerts, service schedules, defects, routes. '
            .'Get-by-ID tools: get_work_order, get_vehicle, get_driver, get_route, get_trip for details on a single item. '
            .'For "Where is [vehicle]?" or "Where is [registration]?": use list_vehicles to find the vehicle (match by registration or name), then get_vehicle with that vehicle\'s ID to get current position (lat/lng) and location_updated_at. Report the coordinates and when the position was last updated; if no position is reported, say so. '
            .'Use list_work_orders for maintenance and repair orders; list_compliance_items for expiring MOT, insurance, licences (expiring_within_days); list_service_schedules for next service due; list_defects for DVIR-style defects; list_routes for route plans. '
            .'Always scope answers to the current organization. Be concise and cite document sources when using document search. '
            .'If the user asks about something you cannot find in tools, say so.';
    }

    public function tools(): iterable
    {
        $orgId = $this->organizationId;
        if ($orgId === null) {
            return [];
        }

        return [
            new FleetDocumentSearch($orgId),
            new ListVehicles($orgId),
            new ListDrivers($orgId),
            new ListTrips($orgId),
            new ListWorkOrders($orgId),
            new ListComplianceItems($orgId),
            new ListAlerts($orgId),
            new ListServiceSchedules($orgId),
            new ListDefects($orgId),
            new ListRoutes($orgId),
            new GetWorkOrder($orgId),
            new GetVehicle($orgId),
            new GetDriver($orgId),
            new GetRoute($orgId),
            new GetTrip($orgId),
        ];
    }
}
