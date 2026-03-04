<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\ListVehiclesTool;
use App\Mcp\Tools\ListWorkOrdersTool;
use App\Mcp\Tools\UsersIndexTool;
use App\Mcp\Tools\UsersShowTool;
use Laravel\Mcp\Server;

final class ApiServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Api Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        This server exposes API capabilities as tools. Use users_index/users_show for users; fleet_list_vehicles and fleet_list_work_orders for Fleet data (scoped to the user's default organization). All tools require an authenticated session (Sanctum).
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = [
        UsersIndexTool::class,
        UsersShowTool::class,
        ListVehiclesTool::class,
        ListWorkOrdersTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
