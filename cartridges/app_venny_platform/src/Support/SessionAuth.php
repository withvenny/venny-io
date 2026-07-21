<?php

declare(strict_types=1);

namespace VennyIO\Support;

use PDO;

final class SessionAuth
{
    /**
     * Validates X-Venny-Session-Id for the authenticated app/tenant and returns
     * the active session context. This is the user-session boundary for browser
     * account areas and other user-scoped facades.
     */
    public static function require(PDO $db, array $appContext): array
    {
        $sessionId = self::providedSessionId();

        if ($sessionId === '') {
            Response::json(401, false, 'session id is missing', []);
            exit;
        }

        if (!Ids::is('session', $sessionId)) {
            Response::json(401, false, 'session id is invalid', []);
            exit;
        }

        $appId = (string) ($appContext['app_id'] ?? $appContext['created_for_app_id'] ?? '');
        if (trim($appId) === '') {
            Response::json(401, false, 'authenticated app context is missing', []);
            exit;
        }

        $sql = <<<'SQL'
            SELECT
                s.session_id,
                s.session_attributes,
                s.session_user_id,
                s.session_useragent,
                s.session_expiresat,
                s.session_revokedat,
                s.session_createdat,
                s.session_lastseenat,
                s.created_by_user_id,
                s.created_for_app_id,
                s.event_id,
                s.process_id,
                s.access,
                s.status,
                s.active,
                s.time_started,
                s.time_updated,
                u.user_id,
                u.user_email,
                u.user_username,
                u.user_displayname
            FROM sessions s
            INNER JOIN users u
                ON u.user_id = s.session_user_id
               AND u.created_for_app_id = s.created_for_app_id
               AND u.active = 1
               AND u.status = 'active'
            WHERE s.session_id = :session_id
              AND s.created_for_app_id = :app_id
              AND s.active = 1
              AND s.status = 'active'
              AND s.session_revokedat IS NULL
              AND s.session_expiresat > now()
            LIMIT 1;
        SQL;

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':session_id' => $sessionId,
            ':app_id' => $appId,
        ]);

        $row = $stmt->fetch() ?: [];
        if ($row === []) {
            Response::json(401, false, 'session is invalid or expired', []);
            exit;
        }

        $touch = $db->prepare('UPDATE sessions SET session_lastseenat = now(), time_updated = now() WHERE session_id = :session_id AND created_for_app_id = :app_id');
        $touch->execute([
            ':session_id' => $sessionId,
            ':app_id' => $appId,
        ]);

        if (array_key_exists('session_attributes', $row) && is_string($row['session_attributes'])) {
            $decoded = json_decode($row['session_attributes'], true);
            $row['session_attributes'] = is_array($decoded) ? $decoded : new \stdClass();
        }

        return $row;
    }

    public static function providedSessionId(): string
    {
        $sessionId = $_SERVER['HTTP_X_VENNY_SESSION_ID']
            ?? $_SERVER['REDIRECT_HTTP_X_VENNY_SESSION_ID']
            ?? '';

        return is_string($sessionId) ? trim($sessionId) : '';
    }
}
