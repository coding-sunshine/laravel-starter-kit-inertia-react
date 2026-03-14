<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\ContactSearchTool;
use App\Mcp\Tools\LotsFilterTool;
use App\Mcp\Tools\PipelineSummaryTool;
use App\Mcp\Tools\UsersIndexTool;
use App\Mcp\Tools\UsersShowTool;
use Laravel\Mcp\Server;
use Override;

final class ApiServer extends Server
{
    /**
     * The MCP server's name.
     */
    #[Override]
    protected string $name = 'Api Server';

    /**
     * The MCP server's version.
     */
    #[Override]
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    #[Override]
    protected string $instructions = <<<'MARKDOWN'
        This server exposes Fusion CRM capabilities as tools. Tools:
        - users_index / users_show: list and view users
        - contacts_search: search CRM contacts (returns ContactCard data for C1 UI)
        - lots_filter: find available lots matching buyer criteria (returns PropertyCard data)
        - pipeline_summary: get reservation pipeline by stage (returns PipelineFunnel data)
        All tools require an authenticated session.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Server\Tool>>
     */
    #[Override]
    protected array $tools = [
        UsersIndexTool::class,
        UsersShowTool::class,
        ContactSearchTool::class,
        LotsFilterTool::class,
        PipelineSummaryTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    #[Override]
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Server\Prompt>>
     */
    #[Override]
    protected array $prompts = [
        //
    ];
}
