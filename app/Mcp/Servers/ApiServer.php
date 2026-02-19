<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\SidingsIndexTool;
use App\Mcp\Tools\UserSidingsTool;
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
        This server exposes API capabilities for the Railway Rake Management Control System (RRMCS).

        Available tools:
        - users_index: List users with optional filters/sort
        - users_show: Get a single user by ID
        - sidings_index: List all railway sidings (Pakur, Dumka, Kurwa)
        - user_sidings: Get sidings that a specific user can access

        All tools require authenticated session (Sanctum).
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = [
        UsersIndexTool::class,
        UsersShowTool::class,
        SidingsIndexTool::class,
        UserSidingsTool::class,
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
