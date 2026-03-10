---
name: mcp-development
description: "Develops MCP servers, tools, resources, and prompts. Activates when creating MCP tools, resources, or prompts; setting up AI integrations; debugging MCP connections; working with routes/ai.php; or when the user mentions MCP, Model Context Protocol, AI tools, AI server, or building tools for AI assistants."
license: MIT
metadata:
  author: laravel
---

# MCP Development

## When to Apply

Activate this skill when:

- Creating MCP tools, resources, or prompts
- Setting up MCP server routes
- Debugging MCP connection issues

## Documentation

Use `search-docs` for detailed Laravel MCP patterns and documentation.

## Basic Usage

Register MCP servers in `routes/ai.php`:

<!-- Register MCP Server -->
```php
use Laravel\Mcp\Facades\Mcp;

Mcp::web();
```

### Creating MCP Primitives

Create MCP tools, resources, prompts, and servers using artisan commands:

```bash
vendor/bin/sail artisan make:mcp-tool ToolName        # Create a tool

vendor/bin/sail artisan make:mcp-resource ResourceName # Create a resource

vendor/bin/sail artisan make:mcp-prompt PromptName    # Create a prompt

vendor/bin/sail artisan make:mcp-server ServerName    # Create a server

```

After creating primitives, register them in your server's `$tools`, `$resources`, or `$prompts` properties.

### Tools

<!-- MCP Tool Example -->
```php
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Request;
use Laravel\Mcp\Server\Response;

class MyTool extends Tool
{
    public function handle(Request $request): Response
    {
        return new Response(['result' => 'success']);
    }
}
```

### Registering Primitives in a Server

Each MCP server must explicitly declare the tools, resources, and prompts it exposes.

<!-- Register Primitives in MCP Server -->
```php
use Laravel\Mcp\Server;

class AppServer extends Server
{
    protected array $tools = [
        \App\Mcp\Tools\MyTool::class,
    ];

    protected array $resources = [
        \App\Mcp\Resources\MyResource::class,
    ];

    protected array $prompts = [
        \App\Mcp\Prompts\MyPrompt::class,
    ];
}
```

## Verification

1. Check `routes/ai.php` for proper registration
2. Test tool via MCP client

## Common Pitfalls

- Running `mcp:start` command (it hangs waiting for input)
- Using HTTPS locally with Node-based MCP clients
- Not using `search-docs` for the latest MCP documentation
- Not registering MCP server routes in `routes/ai.php`
- Do not register `ai.php` in `bootstrap.php`; it is registered automatically.