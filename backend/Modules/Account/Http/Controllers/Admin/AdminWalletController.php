<?php

namespace Modules\Account\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Account\Entities\Wallet;
use Modules\Account\Http\Resources\Admin\AdminWalletResource;

class AdminWalletController extends Controller
{
    private const SORTABLE = ['id', 'balance', 'created_at'];

    /** قائمة المحافظ (مع صاحبها) — بحث باسم/بريد المالك + فرز بالرصيد + ترقيم. */
    public function index(Request $request)
    {
        $this->authorize('view_wallets');

        $query = Wallet::with('user');

        if ($q = trim((string) $request->query('q', ''))) {
            $query->whereHas('user', function ($sub) use ($q): void {
                $sub->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            });
        }

        [$column, $dir] = $this->parseSort((string) $request->query('sort', '-balance'), self::SORTABLE);
        $query->orderBy($column, $dir);

        $items = $query->paginate((int) $request->query('perPage', 15));
        $items->setCollection(
            $items->getCollection()->map(fn (Wallet $w) => (new AdminWalletResource($w))->resolve())
        );

        return $this->dashboardResponse($items);
    }

    /** تعديل الرصيد يدويًّا (إضافة موجبة أو خصم سالب) — يُسجَّل كحركة. */
    public function adjust(Request $request, Wallet $wallet)
    {
        $this->authorize('adjust_wallets');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:120'],
        ]);

        $newBalance = round((float) $wallet->balance + $data['amount'], 2);
        if ($newBalance < 0) {
            return $this->forbiddenResponse(__('Balance cannot go negative.'));
        }

        $tx = is_array($wallet->transactions) ? $wallet->transactions : [];
        $tx[] = [
            'id' => count($tx) + 1,
            'amount' => (float) $data['amount'],
            'label' => $data['note'] ?? __('Admin adjustment'),
            'at' => Carbon::now()->toISOString(),
        ];
        $wallet->update(['balance' => $newBalance, 'transactions' => $tx]);

        return $this->updatedResponse((new AdminWalletResource($wallet->load('user')))->resolve());
    }
}
