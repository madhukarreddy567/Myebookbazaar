<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

/**
 * Append-only audit logging (Phase 2A).
 *
 * Secret values must NEVER be passed in $newValues — callers should pass
 * a placeholder such as '***changed***' instead. This helper also strips
 * any key whose name looks like a secret, as a second line of defence.
 */
class Audit
{
    /** Key fragments that mark a value as secret — never stored. */
    protected const SECRET_HINTS = [
        'password', 'secret', 'token', 'api_key', 'apikey', 'private',
    ];

    public static function record(
        string $action,
        ?string $targetType = null,
        int|string|null $targetId = null,
        array $oldValues = [],
        array $newValues = [],
        ?int $adminId = null,
    ): void {
        try {
            $request = request();

            AuditLog::create([
                'admin_id' => $adminId ?? Auth::guard('admin')->id(),
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId !== null ? (int) $targetId : null,
                'old_values' => self::scrub($oldValues) ?: null,
                'new_values' => self::scrub($newValues) ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 1000) : null,
            ]);
        } catch (QueryException $e) {
            // Audit table missing (pre-migration): never crash the request.
            report($e);
        }
    }

    /**
     * Remove anything that looks like a secret from a values array.
     */
    public static function scrub(array $values): array
    {
        $clean = [];
        foreach ($values as $key => $value) {
            $lower = strtolower((string) $key);
            $isSecret = false;
            foreach (self::SECRET_HINTS as $hint) {
                if (str_contains($lower, $hint)) {
                    $isSecret = true;
                    break;
                }
            }
            $clean[$key] = $isSecret ? '***redacted***' : $value;
        }

        return $clean;
    }
}
