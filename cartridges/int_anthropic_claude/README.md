# int_anthropic_claude

## Purpose

`int_anthropic_claude` gives Venny I/O a reusable native-PHP adapter for Anthropic's Claude API using Anthropic's official PHP SDK rather than embedding direct HTTP details throughout application cartridges. It exposes the Messages API, streaming, token counting, client/server tool definitions, structured outputs, prompt caching controls, file operations, and normalized provider diagnostics while preserving Claude-specific response data when needed. The cartridge owns Anthropic authentication, SDK construction, request normalization, provider exception translation, and response normalization. It does not own Venny prompts, conversations, agents, business workflows, tool implementations, memory, queueing, or application policy. Those remain platform/application concerns. The separate `int_anthropic_agent_sdk` cartridge is reserved for Claude Agent SDK execution and its more privileged filesystem/tool-oriented runtime.

## Provider

Anthropic

## Tool

Claude API

Official PHP SDK:

https://github.com/anthropics/anthropic-sdk-php

Official PHP API reference:

https://platform.claude.com/docs/en/api/php

Current cartridge dependency:

```text
anthropic-ai/sdk ^0.44.0
```

## Installation

```bash
composer install
```

The official Anthropic PHP SDK requires a PSR-18 HTTP implementation. This cartridge includes Guzzle as the concrete implementation.

## Configuration

### `v_ANTHROPIC_API_KEY`

Required secret.

Create an Anthropic API key in the Anthropic Console and store it only as server-side configuration.

Expected format commonly begins:

```text
sk-ant-
```

The cartridge requires a non-empty key but intentionally does not hard-fail solely on a prefix so Anthropic can evolve credential formats without forcing a cartridge patch.

### `v_ANTHROPIC_DEFAULT_MODEL`

Optional.

Default Claude model used when a request omits `model`.

Example:

```text
claude-sonnet-5
```

Model availability changes over time. Business Manager should treat this as configurable rather than hard-coding one model as permanently valid.

### `v_ANTHROPIC_MAX_TOKENS`

Optional.

Default maximum output token count when a request omits `max_tokens`.

Default:

```text
4096
```

## Architectural Boundary

```text
Venny application / agent orchestration
        ↓
AiProviderInterface
        ↓
int_anthropic_claude
        ↓
Anthropic Claude API
```

This cartridge is the low-level Claude model provider.

It is not the Claude Agent SDK.

## Capabilities

### Messages

```php
createMessage(array $request)
```

Minimum input:

```php
[
    'messages' => [
        ['role' => 'user', 'content' => 'Hello Claude']
    ]
]
```

Optional supported Claude request fields are forwarded when supplied, including:

```text
model
max_tokens
system
cache_control
container
inference_geo
metadata
output_config
service_tier
stop_sequences
thinking
tool_choice
tools
top_k
top_p
user_profile_id
```

The cartridge intentionally does not introduce synthetic Claude parameters.

### Streaming

```php
streamMessage(array $request): iterable
```

Uses the official SDK `messages->createStream()` API and yields provider events.

### Count tokens

```php
countTokens(array $request)
```

Uses the official Messages token-count endpoint.

### Tool use

Tool definitions are passed through using Anthropic's documented `tools` and `tool_choice` request fields.

Venny executes client tools outside this integration. This cartridge does not silently execute arbitrary application functions.

### Structured outputs

The official PHP SDK supports structured output helpers. The generic cartridge also accepts documented `output_config` values for callers that intentionally use them.

### Prompt caching

Documented Anthropic `cache_control` structures can be passed in messages, tools, system blocks, or top-level request fields as supported by the Claude API.

### Files

The official SDK exposes Anthropic Files APIs.

This cartridge includes generic helpers:

```text
uploadFile()
retrieveFile()
deleteFile()
```

Applications should not treat Anthropic Files as Venny's canonical file store.

## Provider Result

Normalized result:

```php
[
    'success' => true,
    'provider' => 'anthropic',
    'tool' => 'claude',
    'operation' => 'messages_create',
    'provider_id' => 'msg_...',
    'data' => [...],
    'metadata' => [...]
]
```

## Health Check

The default health check is local and non-billable.

It validates configuration and constructs the official Anthropic client.

It does **not** submit a Claude generation.

A remote test should be an explicit Business Manager action because model requests can incur usage charges.

## Error Handling

Normalized exceptions:

```text
ConfigurationException
AuthenticationException
RateLimitException
ValidationException
ProviderException
```

Provider diagnostics preserve exception class and provider-safe context but never include the API key.

## Business Manager

Business Manager exposes:

```text
installed version
official SDK version requirement
configured/not configured
default model
default max tokens
per-key setup instructions
local health
documentation
```

Secrets are masked.

## Security

Never log:

```text
v_ANTHROPIC_API_KEY
Authorization headers
sensitive prompt/message bodies unless explicitly permitted by Venny logging policy
raw uploaded files unless required by an approved diagnostic workflow
```

Tool-use requests should be subject to Venny's own tool authorization policy.

## Persistence

No SQL.

The cartridge is provider transport/runtime only.

## Documentation

Claude API:

https://platform.claude.com/docs/en/api/overview

Messages:

https://platform.claude.com/docs/en/api/messages

PHP reference:

https://platform.claude.com/docs/en/api/php

Official PHP SDK:

https://github.com/anthropics/anthropic-sdk-php
