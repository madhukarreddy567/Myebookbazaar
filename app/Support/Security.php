<?php

namespace App\Support;

use App\Models\SecurityLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

/**
 * Phase 6: one-line security event logging.
 *
 *   Security::log('login.failed', 'Customer login failed', ['email' => $email]);
 *
 * The current admin/user ids, IP and user agent are captured
 * automatically. Never pass passwords, tokens or secrets in $meta —
 * they are scrubbed the same way as the audit log.
 */
class Security
{
    public static function log(string $event, ?string $message = null, array $meta = []): void
    {
        try {
            $request = request();

            SecurityLog::create([
                'admin_id' => Auth::guard('admin')->id(),
                'user_id' => Auth::guard('web')->id(),
                'event' => mb_substr($event, 0, 60),
                'message' => $message !== null ? mb_substr($message, 0, 2000) : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 1000) : null,
                'meta' => Audit::scrub($meta) ?: null,
            ]);
        } catch (QueryException $e) {
            // Table missing (pre-migration): never crash the request.
            report($e);
        }
    }
}
