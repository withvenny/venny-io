# int_anthropic_agent_sdk

## Purpose

`int_anthropic_agent_sdk` gives Venny I/O controlled access to Anthropic's Claude Agent SDK while preserving Venny's PHP-facing cartridge contract. Anthropic does not currently publish an official PHP Agent SDK; the official Agent SDK implementations are TypeScript and Python. Rather than depend on an unofficial PHP port, this cartridge uses a small PHP process bridge to an isolated Node.js runtime containing Anthropic's official `@anthropic-ai/claude-agent-sdk`. It supports bounded agent queries, streamed SDK event capture, explicit working directories, built-in tool allowlists, documented permission modes, model/system configuration, and selected advanced SDK options. Because Agent SDK execution can read files, edit files, run commands, invoke MCP servers, and perform autonomous workflows, the cartridge is intentionally restrictive by default: no built-in tools, no inherited settings sources, no permission bypass, and no persistent session unless an application explicitly opts in.

## Provider

Anthropic

## Tool

Claude Agent SDK

Official package:

```text
@anthropic-ai/claude-agent-sdk
```

Current package range:

```text
^0.3.250
```

Official package documentation states that the Agent SDK provides Claude Code capabilities programmatically, including understanding codebases, editing files, running commands, and executing complex workflows.

## Why this cartridge is hybrid

Venny I/O's normal cartridge runtime is PHP.

Anthropic's official Claude API has a PHP SDK, so `int_anthropic_claude` is native PHP.

Anthropic's Agent SDK is officially available in TypeScript and Python, not PHP.

Therefore:

```text
Venny PHP
   ↓
int_anthropic_agent_sdk PHP bridge
   ↓ stdin/stdout JSON
official Node Agent SDK runtime
   ↓
@anthropic-ai/claude-agent-sdk
```

This keeps Venny on an official Anthropic implementation without introducing an unsupported PHP reimplementation.

## Installation

PHP:

```bash
composer install
```

Agent runtime:

```bash
cd agent
npm install
```

Node.js 20 or later is required by the current Anthropic TypeScript ecosystem.

## Configuration

### `v_ANTHROPIC_API_KEY`

Required secret when Agent SDK execution is authenticated through the Anthropic API.

### `v_CLAUDE_AGENT_NODE_BINARY`

Optional.

Default:

```text
node
```

Set an absolute executable path when the runtime cannot resolve `node` through PATH.

### `v_CLAUDE_AGENT_WORKING_DIRECTORY`

Optional but strongly recommended for filesystem/tool-enabled agents.

This is the default filesystem working directory supplied to Agent SDK queries.

Do not point unrestricted agents at the entire application filesystem.

### `v_CLAUDE_AGENT_DEFAULT_MODEL`

Optional.

Default Claude model supplied to agent queries.

### `v_CLAUDE_AGENT_PERMISSION_MODE`

Optional.

Default:

```text
default
```

This cartridge deliberately rejects:

```text
bypassPermissions
```

in v1.

Any future permission-bypass support should require an explicit Venny privileged-execution policy rather than a casual environment setting.

### `v_CLAUDE_AGENT_MAX_TURNS`

Optional.

Default:

```text
8
```

Provides a bounded default maximum number of agent turns.

## Safe defaults

Every query starts from:

```javascript
{
  tools: [],
  allowedTools: [],
  disallowedTools: [],
  permissionMode: "default",
  settingSources: [],
  persistSession: false
}
```

This is deliberate.

The Agent SDK can provide powerful local capabilities. Venny should explicitly grant tools rather than inherit the broad Claude Code environment by accident.

## Capabilities

### Agent query

```php
query(string $prompt, array $options = [])
```

Supported pass-through options include documented Agent SDK controls such as:

```text
model
systemPrompt
maxTurns
cwd
tools
allowedTools
disallowedTools
permissionMode
settingSources
persistSession
includePartialMessages
mcpServers
agents
skills
plugins
betas
thinking
sandbox
strictMcpConfig
```

The cartridge does not invent new Anthropic options.

### Event stream capture

The Agent SDK returns an async event stream.

The Node bridge consumes that stream and returns:

```text
all emitted SDK messages
final result event, when present
```

The first cartridge version returns the completed JSON envelope to PHP.

A future Venny worker transport could expose true incremental inter-process streaming if we need live UI rendering.

### Working directory

`cwd` can be supplied per request or defaulted from:

```text
v_CLAUDE_AGENT_WORKING_DIRECTORY
```

Applications should use the narrowest practical directory.

### Tools

No tools are enabled by default.

Example:

```php
$result = $provider->query(
    'Inspect the repository and explain the failing test.',
    [
        'tools' => ['Read', 'Glob', 'Grep'],
        'allowedTools' => ['Read', 'Glob', 'Grep'],
    ]
);
```

Grant `Bash`, file editing, MCP, plugins, or subagents only to workflows that actually require them.

## API cartridge vs Agent SDK cartridge

Use:

```text
int_anthropic_claude
```

when Venny needs model inference, structured outputs, Claude tool calls, token counting, streaming, or files through the standard Claude API.

Use:

```text
int_anthropic_agent_sdk
```

when Venny intentionally needs an autonomous agent runtime with Claude Code-like capabilities and controlled access to local tools/files/workflows.

## Provider Result

```php
[
    'success' => true,
    'provider' => 'anthropic',
    'tool' => 'agent_sdk',
    'operation' => 'query',
    'provider_id' => null,
    'data' => [
        'messages' => [...],
        'result' => [...]
    ],
    'metadata' => [...]
]
```

## Health Check

The local health check verifies:

```text
configuration
Node executable
agent/runner.mjs
agent/package.json
node_modules/@anthropic-ai/claude-agent-sdk presence
working-directory validity when configured
```

It does not execute an agent or incur model usage.

## Business Manager

Business Manager must make the privileged nature of this integration visible.

It should show:

```text
Node runtime state
Agent SDK dependency state
working directory
default model
permission mode
maximum turns
API key state
safe-default policy
health
```

Secrets remain masked.

## Security

This cartridge requires stronger controls than an ordinary inference adapter.

Venny applications should assume that enabled Agent SDK tools may interact with:

```text
filesystem
shell commands
source code
network-connected MCP servers
plugins
subagents
local project configuration
```

Recommended policy:

```text
deny tools by default
allowlist tools per workflow
use narrow working directories
run workers with least OS privilege
avoid unrestricted shell access
avoid permission bypass
do not inherit local/user Claude settings
bound max turns
audit tool grants
keep credentials outside agent-readable files
```

## Session isolation

The bridge defaults to:

```text
settingSources = []
persistSession = false
```

This reduces accidental inheritance of local Claude configuration.

Agent SDK behavior evolves quickly, so isolation should be revalidated whenever the SDK is upgraded.

## Persistence

No SQL.

Venny's agent/conversation domain should own any durable session, run, audit, approval, or tool-execution records.

## Documentation

Agent SDK:

https://platform.claude.com/docs/en/agent-sdk/overview

Official TypeScript package:

https://github.com/anthropics/claude-agent-sdk-typescript

npm:

https://www.npmjs.com/package/@anthropic-ai/claude-agent-sdk
