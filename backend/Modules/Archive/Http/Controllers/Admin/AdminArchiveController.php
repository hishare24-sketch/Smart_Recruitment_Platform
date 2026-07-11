<?php

namespace Modules\Archive\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Marketplace\Entities\MarketRequest;
use Modules\Marketplace\Entities\Opportunity;
use Modules\Survey\Entities\Survey;

/**
 * الأرشيف ودورة حياة البيانات — عرض موحّد للمحذوف حذفًا ناعمًا عبر الموارد،
 * مع استعادة وإزالة نهائيّة. الحذف من شاشات الموارد صار يؤرشف تلقائيًّا (قابل للاستعادة).
 */
class AdminArchiveController extends Controller
{
    /** سجلّ الموارد القابلة للأرشفة: النوع → [الكيان]. */
    private function registry(): array
    {
        return [
            'opportunities' => Opportunity::class,
            'requests' => MarketRequest::class,
            'surveys' => Survey::class,
        ];
    }

    /** قائمة موحّدة بالمؤرشفات (فلتر نوع اختياريّ) + ترقيم. */
    public function index(Request $request)
    {
        $this->authorize('view_archive');

        $type = $request->query('type');
        $items = collect();

        foreach ($this->registry() as $key => $model) {
            if ($type && $type !== $key) {
                continue;
            }
            $trashed = $model::onlyTrashed()->get();
            foreach ($trashed as $row) {
                $items->push([
                    'type' => $key,
                    'id' => $row->id,
                    'title' => $row->title ?? '—',
                    'deletedAt' => optional($row->deleted_at)->toISOString(),
                ]);
            }
        }

        $items = $items->sortByDesc('deletedAt')->values();

        $perPage = (int) $request->query('perPage', 20);
        $page = max(1, (int) $request->query('page', 1));
        $paginator = new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page);

        return $this->dashboardResponse($paginator);
    }

    /** إحصاءات — عدد المؤرشف لكلّ نوع + الإجمالي. */
    public function stats()
    {
        $this->authorize('view_archive');

        $byType = [];
        $total = 0;
        foreach ($this->registry() as $key => $model) {
            $c = $model::onlyTrashed()->count();
            $byType[] = ['label' => $key, 'value' => $c];
            $total += $c;
        }

        return $this->dataResponse(['total' => $total, 'byType' => $byType]);
    }

    /** استعادة عنصر مؤرشف. */
    public function restore(Request $request)
    {
        $this->authorize('manage_archive');

        [$model, $id] = $this->resolve($request);
        $model::onlyTrashed()->findOrFail($id)->restore();

        return $this->updatedResponse(['restored' => true]);
    }

    /** إزالة نهائيّة (لا رجعة). */
    public function purge(Request $request)
    {
        $this->authorize('manage_archive');

        [$model, $id] = $this->resolve($request);
        $model::onlyTrashed()->findOrFail($id)->forceDelete();

        return $this->updatedResponse(['purged' => true]);
    }

    /** يحلّ [كيان, id] من طلب {type, id} مع تحقّق النوع. */
    private function resolve(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required', 'integer'],
        ]);
        $registry = $this->registry();
        abort_unless(isset($registry[$data['type']]), 422, 'Unknown archive type');

        return [$registry[$data['type']], $data['id']];
    }
}
