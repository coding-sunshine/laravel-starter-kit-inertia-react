<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\LotsIndex;
use App\Ai\Tools\ProjectsIndex;
use App\Ai\Tools\ReservationsIndex;
use App\Ai\Tools\SalesIndex;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Agent for property and sales questions: read-only access to projects, lots, reservations, sales.
 * Use forUser($user) or continue($conversationId, $user). Expose via chat with agent=property.
 */
final class PropertySalesAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function instructions(): string
    {
        return 'You are a property and sales assistant. You can list and search projects (developments), lots, property reservations, and sales in the current organization. '
            .'Use the projects_index tool to find projects by title or description. '
            .'Use the lots_index tool to find lots, optionally filtered by project_id. '
            .'Use the reservations_index and sales_index tools to list recent reservations and sales. '
            .'Summarize results clearly. You have read-only access only.';
    }

    public function tools(): iterable
    {
        return [
            new ProjectsIndex,
            new LotsIndex,
            new ReservationsIndex,
            new SalesIndex,
        ];
    }
}
