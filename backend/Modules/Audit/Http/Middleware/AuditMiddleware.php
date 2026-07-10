<?php

namespace Modules\Audit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Audit\Entities\AuditLog;

/**
 * يسجّل أفعال الأدمن المُعدِّلة تلقائيًّا (POST/PUT/PATCH/DELETE تحت api/admin).
 * يُركّب على مجموعة حارس الأدمن بعد auth:sanctum ليتوفّر المستخدم.
 */
class AuditMiddleware
{
    private const MUTATING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** أفعال ذات معنًى في آخر المسار (بدل map الطريقة). */
    private const VERBS = ['close', 'approve', 'reject', 'suspend', 'activate', 'adjust', 'transactions', 'permissions', 'admin-role'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (in_array($request->method(), self::MUTATING, true)) {
            $this->record($request, $response->getStatusCode());
        }

        return $response;
    }

    private function record(Request $request, int $status): void
    {
        try {
            $user = current_user();
            $segments = $request->segments();            // [api, admin, resource, ...]
            $resource = $segments[2] ?? null;
            $last = end($segments);

            // الفعل: كلمة معناها في آخر المسار، وإلّا يُشتقّ من الطريقة
            $action = in_array($last, self::VERBS, true)
                ? $last
                : match ($request->method()) {
                    'POST' => 'create',
                    'PUT', 'PATCH' => 'update',
                    'DELETE' => 'delete',
                    default => 'action',
                };

            // معرّف الهدف: أوّل مقطع رقميّ بعد المورد
            $targetId = null;
            foreach (array_slice($segments, 3) as $seg) {
                if (ctype_digit($seg)) {
                    $targetId = (int) $seg;
                    break;
                }
            }

            AuditLog::create([
                'actor_id' => $user?->id,
                'actor_name' => $user?->name,
                'method' => $request->method(),
                'resource' => $resource,
                'action' => $action,
                'path' => $request->path(),
                'target_id' => $targetId,
                'status' => $status,
                'meta' => null,
                'ip' => $request->ip(),
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // التدقيق لا يجب أن يكسر الطلب أبدًا
        }
    }
}
