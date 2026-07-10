<?php

namespace Modules\Survey\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Survey\Entities\Survey;
use Modules\Survey\Http\Resources\Admin\AdminSurveyResource;

class AdminSurveyController extends Controller
{
    private const SORTABLE = ['id', 'title', 'state', 'points_pool', 'created_at'];

    /** قائمة الاستبيانات — بحث + فلترة حالة + فرز + ترقيم. */
    public function index(Request $request)
    {
        $this->authorize('view_surveys');

        $query = Survey::with('user');

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where('title', 'like', "%{$q}%");
        }
        if ($state = $request->query('state')) {
            $query->where('state', $state);
        }

        [$column, $dir] = $this->parseSort((string) $request->query('sort', '-id'), self::SORTABLE);
        $query->orderBy($column, $dir);

        $items = $query->paginate((int) $request->query('perPage', 15));
        $items->setCollection(
            $items->getCollection()->map(fn (Survey $s) => (new AdminSurveyResource($s))->resolve())
        );

        return $this->dashboardResponse($items);
    }

    /** إغلاق استبيان (إيقاف الاستجابات). */
    public function close(Survey $survey)
    {
        $this->authorize('close_surveys');
        $survey->update(['state' => 'closed']);

        return $this->updatedResponse((new AdminSurveyResource($survey->load('user')))->resolve());
    }

    /** حذف استبيان. */
    public function destroy(Survey $survey)
    {
        $this->authorize('delete_surveys');
        $survey->delete();

        return $this->updatedResponse(null, __('Deleted successfully'));
    }
}
