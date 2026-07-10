<?php

namespace Modules\Account\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Account\Entities\Plan;
use Modules\Account\Http\Resources\Admin\AdminPlanResource;

class AdminPlanController extends Controller
{
    private const SORTABLE = ['id', 'key', 'name', 'price', 'sort'];

    /** كتالوج الباقات — كلّها (فرز بالترتيب افتراضًا). */
    public function index(Request $request)
    {
        $this->authorize('view_plans');

        $query = Plan::query();

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($sub) use ($q): void {
                $sub->where('name', 'like', "%{$q}%")->orWhere('key', 'like', "%{$q}%");
            });
        }

        [$column, $dir] = $this->parseSort((string) $request->query('sort', 'sort'), self::SORTABLE, 'sort');
        $query->orderBy($column, $dir);

        $items = $query->paginate((int) $request->query('perPage', 15));
        $items->setCollection(
            $items->getCollection()->map(fn (Plan $p) => (new AdminPlanResource($p))->resolve())
        );

        return $this->dashboardResponse($items);
    }

    /** تعديل باقة — السعر/الاسم/حدّ الاستبيانات/المزايا/التفعيل. */
    public function update(Request $request, Plan $plan)
    {
        $this->authorize('update_plans');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'survey_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['string', 'max:200'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $plan->update($data);

        return $this->updatedResponse((new AdminPlanResource($plan->fresh()))->resolve());
    }
}
