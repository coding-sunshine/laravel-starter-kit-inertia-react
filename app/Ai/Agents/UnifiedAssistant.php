<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Middleware\WithMemoryUnlessUnavailable;
use App\Ai\Tools\Fleet\CreateAlert;
use App\Ai\Tools\Fleet\CreateWorkOrder;
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
use App\Ai\Tools\Fleet\ScheduleInspection;
use App\Ai\Tools\Fleet\SendDriverReminder;
use App\Ai\Tools\Fleet\UpdateComplianceItem;
use App\Ai\Tools\ListUserOrganizations;
use App\Ai\Tools\SearchHelpArticles;
use Eznix86\AI\Memory\Tools\RecallMemory;
use Eznix86\AI\Memory\Tools\StoreMemory;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Unified Assistant: one agent that loads tools based on context (scope).
 * Use for Fleet Assistant (scope=fleet), general chat (scope=org), or help-only (scope=help).
 */
final class UnifiedAssistant implements Agent, Conversational, HasMiddleware, HasTools
{
    use Promptable;
    use RemembersConversations;

    /** @param  array{page?: string, entity_id?: int, user_id?: int}  $context */
    public function __construct(
        public string $scope,
        public ?int $organizationId = null,
        public ?int $userId = null,
        public array $context = [],
    ) {}

    public static function forFleet(?int $organizationId, ?int $userId): self
    {
        return new self('fleet', $organizationId, $userId, ['user_id' => $userId]);
    }

    public static function forOrg(?int $userId): self
    {
        return new self('org', null, $userId, ['user_id' => $userId]);
    }

    public static function forHelp(?int $userId = null): self
    {
        return new self('help', null, $userId, ['user_id' => $userId]);
    }

    public function instructions(): string
    {
        return match ($this->scope) {
            'fleet' => $this->fleetInstructions(),
            'help' => 'You are a help assistant. Use the Search Help Articles tool to find documentation. Answer only from help content; if nothing is found, say so.',
            'org' => 'You are a helpful assistant with memory. Use Store Memory to save important facts the user shares. Use Recall Memory when you need prior context. Use Search Help Articles when the user asks how to do something. Use List User Organizations when the user asks about their organizations or workspaces.',
            default => 'You are a helpful assistant. Use the tools available to you to answer the user.',
        };
    }

    public function tools(): iterable
    {
        return match ($this->scope) {
            'fleet' => $this->fleetTools(),
            'help' => [new SearchHelpArticles],
            'org' => $this->orgTools(),
            default => [],
        };
    }

    public function middleware(): array
    {
        if ($this->scope !== 'org') {
            return [];
        }

        $ctx = $this->context;
        $limit = (int) config('memory.middleware_recall_limit', 5);

        return [
            new WithMemoryUnlessUnavailable($ctx, limit: $limit),
        ];
    }

    private function fleetInstructions(): string
    {
        return 'You are the Fleet Intelligence Assistant. Answer only using fleet data and the documents provided by your tools. '
            .'You can: create work orders (create_work_order), create alerts (create_alert), schedule inspections (schedule_inspection), send driver reminders (send_driver_reminder), update compliance items (update_compliance_item). '
            .'Search fleet documents (MOT, V5C, insurance, service history) with the document search tool—when you use it, always cite the source (document name or reference). '
            .'List tools: vehicles, drivers, trips, work orders, compliance items, alerts, service schedules, defects, routes. '
            .'Get-by-ID tools: get_work_order, get_vehicle, get_driver, get_route, get_trip for details on a single item. '
            .'For "Where is [vehicle]?" or "Where is [registration]?": use list_vehicles to find the vehicle (match by registration or name), then get_vehicle with that vehicle\'s ID to get current position (lat/lng) and location_updated_at. Report the coordinates and when the position was last updated; if no position is reported, say so. '
            .'Use list_work_orders for maintenance and repair orders; list_compliance_items for expiring MOT, insurance, licences (expiring_within_days); list_service_schedules for next service due; list_defects for DVIR-style defects; list_routes for route plans. '
            .'Always scope answers to the current organization. Be concise and cite document sources when using document search. '
            .'If the user asks about something you cannot find in tools, say so.';
    }

    /** @return iterable<\Laravel\Ai\Contracts\Tool> */
    private function fleetTools(): iterable
    {
        $orgId = $this->organizationId;
        if ($orgId === null) {
            return [];
        }

        return [
            new CreateWorkOrder($orgId, $this->userId ?? 0),
            new CreateAlert($orgId, $this->userId ?? 0),
            new ScheduleInspection($orgId, $this->userId ?? 0),
            new SendDriverReminder($orgId, $this->userId ?? 0),
            new UpdateComplianceItem($orgId, $this->userId ?? 0),
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

    /** @return iterable<\Laravel\Ai\Contracts\Tool> */
    private function orgTools(): iterable
    {
        $ctx = $this->context;
        $userId = $ctx['user_id'] ?? $this->userId;
        $recallLimit = 10;

        return [
            (new RecallMemory)->context($ctx)->limit($recallLimit),
            (new StoreMemory)->context($ctx),
            new SearchHelpArticles,
            new ListUserOrganizations($userId),
        ];
    }
}
