<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$filesScanned = 0;

$ignoredDirectories = ['.git', 'vendor', '__MACOSX'];
$allowedIpAddresses = ['127.0.0.1', '0.0.0.0'];

$highConfidenceSecretPatterns = [
    'AWS access key' => '/\b(?:AKIA|ASIA)[0-9A-Z]{16}\b/',
    'Google API key' => '/\bAIza[0-9A-Za-z_-]{35}\b/',
    'SendGrid API key' => '/\bSG\.[A-Za-z0-9_-]{20,}\.[A-Za-z0-9_-]{20,}\b/',
    'Stripe secret key' => '/\b(?:sk|rk)_(?:live|test)_[0-9A-Za-z]{16,}\b/',
    'Anthropic API key' => '/\bsk-ant-[0-9A-Za-z_-]{20,}\b/',
    'GitHub token' => '/\b(?:github_pat_[0-9A-Za-z_]{20,}|gh[pousr]_[0-9A-Za-z]{20,})\b/',
    'Slack token' => '/\bxox[baprs]-[0-9A-Za-z-]{10,}\b/',
    'private key block' => '/-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----/',
    'credential-bearing URL' => '#\b(?:postgres(?:ql)?|mysql|redis|amqp|mongodb(?:\+srv)?)://[^\s:/]+:[^\s@/]+@#i',
];

$literalCredentialPattern = '/["\'](?:password|password_confirmation|current_password|new_password|passphrase|api_key|secret|auth_token|access_token|reset_token|private_key|client_secret)["\']\s*:\s*["\']([^"\'\n]+)["\']/i';
$ipv4Pattern = '/(?<![\w.])(?:25[0-5]|2[0-4]\d|1?\d?\d)(?:\.(?:25[0-5]|2[0-4]\d|1?\d?\d)){3}(?![\w.])/';

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $current) use ($ignoredDirectories): bool {
            if ($current->isDir() && in_array($current->getFilename(), $ignoredDirectories, true)) {
                return false;
            }
            return true;
        }
    )
);

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    $relative = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);

    $forbiddenBrand = 'aire' . 'home';

    if (stripos($relative, $forbiddenBrand) !== false) {
        $errors[] = $relative . ': forbidden project-brand reference in filename';
    }

    $content = @file_get_contents($path);
    if ($content === false || str_contains(substr($content, 0, 4096), "\0")) {
        continue;
    }

    $filesScanned++;

    if (stripos($content, $forbiddenBrand) !== false) {
        $errors[] = $relative . ': forbidden project-brand reference in file content';
    }

    foreach ($highConfidenceSecretPatterns as $label => $pattern) {
        if (preg_match($pattern, $content) === 1) {
            $errors[] = $relative . ': possible ' . $label;
        }
    }

    if (preg_match_all($literalCredentialPattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $value = trim((string) ($match[1] ?? ''));
            if ($value === '' || (str_starts_with($value, '{{') && str_ends_with($value, '}}')) || str_starts_with($value, '<')) {
                continue;
            }
            $errors[] = $relative . ': literal credential-like example value';
            break;
        }
    }

    if (preg_match_all($ipv4Pattern, $content, $matches)) {
        foreach (array_unique($matches[0]) as $ipAddress) {
            if (in_array($ipAddress, $allowedIpAddresses, true)) {
                continue;
            }
            $errors[] = $relative . ': non-loopback IPv4 address ' . $ipAddress;
        }
    }
}

$errors = array_values(array_unique($errors));

fwrite(STDOUT, "Venny I/O public release audit\n");
fwrite(STDOUT, "Files scanned: {$filesScanned}\n");
fwrite(STDOUT, 'Findings: ' . count($errors) . "\n");

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDOUT, "FAIL: {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Status: PASS\n");
