<?php

namespace Modules\Audit\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Audit\Entities\AuditLog;
use Modules\Audit\Http\Resources\Admin\AuditLogResource;

class AuditLogController extends Controller
{
    private const SORTABLE = ['id', 'action', 'resource', 'status', 'created_at'];

    /** سجلّ التدقيق — بحث + فلترة (فعل/مورد/طريقة) + فرز + ترقيم. */
    public function index(Request $request)
    {
        $this->authorize('view_audit');

        $query = AuditLog::query();

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($sub) use ($q): void {
                $sub->where('actor_name', 'like', "%{$q}%")->orWhere('path', 'like', "%{$q}%");
            });
        }
        foreach (['action', 'resource', 'method'] as $filter) {
            if ($v = $request->query($filter)) {
                $query->where($filter, $v);
            }
        }

        [$column, $dir] = $this->parseSort((string) $request->query('sort', '-id'), self::SORTABLE);
        $query->orderBy($column, $dir);

        $items = $query->paginate((int) $request->query('perPage', 20));
        $items->setCollection(
            $items->getCollection()->map(fn (AuditLog $l) => (new AuditLogResource($l))->resolve())
        );

        return $this->dashboardResponse($items);
    }

    /** إحصاءات — العدّ الكلّيّ/اليوم + التوزيع بالفعل والمورد + سلسلة 14 يومًا. */
    public function stats()
    {
        $this->authorize('view_audit');

        $total = AuditLog::count();
        $today = AuditLog::where('created_at', '>=', Carbon::now()->startOfDay())->count();

        $byAction = AuditLog::selectRaw('action, COUNT(*) as c')->groupBy('action')->pluck('c', 'action')
            ->map(fn ($c, $a) => ['label' => $a, 'value' => (int) $c])->values();
        $byResource = AuditLog::selectRaw('resource, COUNT(*) as c')->whereNotNull('resource')->groupBy('resource')->pluck('c', 'resource')
            ->map(fn ($c, $a) => ['label' => $a, 'value' => (int) $c])->values();

        $raw = AuditLog::where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');
        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $series[] = ['date' => $date, 'value' => (int) ($raw[$date] ?? 0)];
        }

        return $this->dataResponse([
            'total' => $total,
            'today' => $today,
            'actors' => (int) AuditLog::distinct('actor_id')->count('actor_id'),
            'byAction' => $byAction,
            'byResource' => $byResource,
            'series' => $series,
        ]);
    }
}
