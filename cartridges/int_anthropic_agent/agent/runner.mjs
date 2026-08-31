import { query } from '@anthropic-ai/claude-agent-sdk';

function fail(message, details = {}) {
  process.stdout.write(JSON.stringify({
    success: false,
    error: message,
    details
  }));
  process.exit(1);
}

let raw = '';
for await (const chunk of process.stdin) raw += chunk;

let input;
try {
  input = JSON.parse(raw || '{}');
} catch (error) {
  fail('Invalid JSON input.', { class: error?.constructor?.name });
}

if (!input.prompt || typeof input.prompt !== 'string') {
  fail('prompt is required and must be a string.');
}

const source = input.options && typeof input.options === 'object' ? input.options : {};
const options = {
  // Safe Venny defaults:
  tools: Array.isArray(source.tools) ? source.tools : [],
  allowedTools: Array.isArray(source.allowedTools) ? source.allowedTools : [],
  disallowedTools: Array.isArray(source.disallowedTools) ? source.disallowedTools : [],
  permissionMode: source.permissionMode || 'default',
  settingSources: Array.isArray(source.settingSources) ? source.settingSources : [],
  persistSession: source.persistSession === true,
};

const optionalKeys = [
  'model',
  'systemPrompt',
  'maxTurns',
  'cwd',
  'includePartialMessages',
  'mcpServers',
  'agents',
  'skills',
  'plugins',
  'betas',
  'thinking',
  'sandbox',
  'strictMcpConfig',
];

for (const key of optionalKeys) {
  if (source[key] !== undefined) options[key] = source[key];
}

// Venny v1 deliberately refuses automatic permission bypass.
// A future explicit privileged execution policy can add it with BM-visible controls.
if (
  options.permissionMode === 'bypassPermissions' ||
  source.allowDangerouslySkipPermissions === true
) {
  fail('Permission bypass is disabled by int_anthropic_agent_sdk v1.');
}

const messages = [];
let finalResult = null;

try {
  for await (const message of query({
    prompt: input.prompt,
    options,
  })) {
    messages.push(message);
    if (message?.type === 'result') finalResult = message;
  }
} catch (error) {
  fail(error?.message || 'Claude Agent SDK query failed.', {
    class: error?.constructor?.name,
  });
}

process.stdout.write(JSON.stringify({
  success: true,
  messages,
  result: finalResult,
}));
