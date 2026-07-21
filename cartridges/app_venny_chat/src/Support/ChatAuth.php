<?php

declare(strict_types=1);

namespace VennyIO\Support;

use PDO;
use VennyIO\Kernel\Request;

final class ChatAuth
{
    /**
     * Chat requires both the app-level Bearer key and an active user session.
     *
     * Headers supported by the reference UIs:
     * - Authorization: Bearer {api_key}
     * - X-Venny-Session-Id: {session_id}
     *
     * session_id is also accepted in query/body for Postman convenience.
     */
    public static function require(PDO $db, Request $request): array
    {
        $apiContext = ApiKeyAuth::require($db);
        $appId = TenantScope::appId($apiContext);
        $sessionId = self::sessionId($request);

        if ($sessionId === '') {
            Response::json(401, false, 'active user session is required', [
                'required_header' => 'X-Venny-Session-Id',
            ]);
            exit;
        }

        if (!Ids::is('session', $sessionId)) {
            Response::json(401, false, 'session id is invalid', []);
            exit;
        }

        $stmt = $db->prepare(<<<'SQL'
            SELECT
                session_id,
                session_user_id,
                created_for_app_id,
                status,
                active,
                session_expiresat,
                session_revokedat
            FROM sessions
            WHERE session_id = :session_id
              AND created_for_app_id = :app_id
              AND active = 1
              AND status = 'active'
              AND session_revokedat IS NULL
              AND session_expiresat > now()
            LIMIT 1
        SQL);

        $stmt->execute([
            ':session_id' => $sessionId,
            ':app_id' => $appId,
        ]);

        $session = $stmt->fetch() ?: [];
        $userId = trim((string) ($session['session_user_id'] ?? ''));

        if ($session === [] || $userId === '') {
            Response::json(401, false, 'active user session is required', []);
            exit;
        }

        $touch = $db->prepare('UPDATE sessions SET session_lastseenat = now(), time_updated = now() WHERE session_id = :session_id');
        $touch->execute([':session_id' => $sessionId]);

        return [
            'api_key' => $apiContext,
            'app_id' => $appId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'session' => $session,
        ];
    }

    private static function sessionId(Request $request): string
    {
        $server = $request->server;

        foreach (['HTTP_X_VENNY_SESSION_ID', 'HTTP_X_SESSION_ID', 'HTTP_X_CHATIO_SESSION_ID'] as $header) {
            $value = trim((string) ($server[$header] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $input = $request->input();
        return trim((string) ($input['session_id'] ?? $input['venny_session_id'] ?? ''));
    }
}
