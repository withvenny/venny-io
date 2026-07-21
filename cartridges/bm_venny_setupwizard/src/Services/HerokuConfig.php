<?php

declare(strict_types=1);

final class HerokuConfig
{
    public static function command(array $config, string $apiKey): string
    {
        $vars = [
            'APP_ENV' => $config['app_environment'] ?? 'production',
            'APP_NAME' => $config['app_name'] ?? '',
            'APP_DESCRIPTION' => $config['app_description'] ?? '',
            'AWS_ACCESS_KEY_ID' => $config['aws_access_key_id'] ?? '',
            'AWS_SECRET_ACCESS_KEY' => $config['aws_secret_access_key'] ?? '',
            'AWS_REGION' => $config['aws_region'] ?? 'us-east-1',
            'AWS_CONTENT_BUCKET' => $config['aws_content_bucket'] ?? '',
            'VENNY_API_KEY' => $apiKey,
            'SETUP_ENABLED' => 'false',
        ];
        $parts = ['heroku config:set'];
        foreach ($vars as $key => $value) {
            $escaped = str_replace('"', '\\"', (string)$value);
            $parts[] = $key . '="' . $escaped . '"';
        }
        $app = trim((string)($config['heroku_app_name'] ?? ''));
        if ($app !== '') {
            $parts[] = '--app ' . escapeshellarg($app);
        }
        return implode(" \\\n  ", $parts);
    }
}
