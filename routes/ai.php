<?php

declare(strict_types=1);

use App\Mcp\Servers\ApiServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/api', ApiServer::class)->middleware('auth:sanctum');
