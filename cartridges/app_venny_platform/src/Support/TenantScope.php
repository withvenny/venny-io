<?php

declare(strict_types=1);

namespace VennyIO\Support;

final class TenantScope
{
    /**
     * Returns the app/tenant ID resolved from ApiKeyAuth::require().
     * Normal tenant-owned repositories should use this value in:
     * WHERE created_for_app_id = :created_for_app_id
     */
    public static function appId(array $authContext): string
    {
        $appId = trim((string) ($authContext['app_id'] ?? ''));

        if ($appId === '') {
            Response::json(401, false, 'authenticated app context is missing', []);
            exit;
        }

        return $appId;
    }

    public static function bind(array $authContext): array
    {
        return [':created_for_app_id' => self::appId($authContext)];
    }
}
