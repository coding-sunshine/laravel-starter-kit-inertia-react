<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\Fleet\FleetDocumentSearch;
use App\Ai\Tools\Fleet\ListAlerts;
use App\Ai\Tools\Fleet\ListDrivers;
use App\Ai\Tools\Fleet\ListTrips;
use App\Ai\Tools\Fleet\ListVehicles;
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
            .'You can: search fleet documents (MOT, V5C, insurance, service history) with the document search tool; list vehicles, drivers, trips, and alerts with the list tools. '
            .'Always scope answers to the current organization. Be concise and cite sources when using document search. '
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
            new ListAlerts($orgId),
        ];
    }
}
