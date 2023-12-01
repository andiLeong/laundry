<?php

namespace App\Http\Controllers;

use App\Event\OrderCreated;
use App\Http\Validation\OrderValidate;
use App\Models\Order;
use App\Models\OrderPromotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Order::query();

        if ($user->isEmployee()) {
            $query->passDays(7);
        }

        $orders = $query
            ->filters([
                'user_id' => [],
                'description' => [],
                'paid' => [],
                'payment' => [],
                'date' => [
                    'clause' => 'whereBetween',
                    'column' => 'created_at',
                    'value' => [$request->get('start'), $request->get('end')],
                    'should_attach_query' => fn($request) => $request->filled('start') && $request->filled('end'),
                ],
                'include_user' => [
                    'clause' => 'whereNotNull',
                    'column' => 'user_id',
                    'should_attach_query' => fn($request) => $request->get('include_user') == 'true' || $request->get('include_user') == '1',
                ],
                'phone' => [
                    'clause' => 'whereHas',
                    'relationship' => 'user',
                ],
                'first_name' => [
                    'clause' => 'whereHas',
                    'relationship' => 'user',
                ],
                'filter_by_days' => [
                    'clause' => 'callBack',
                    'callback' => function (Builder $query, Request $request) {
                        $day = $request->get('filter_by_days');
                        if ($day === 'today') {
                            return $query->today();
                        }

                        if ($day === 'week') {
                            return $query->currentWeek();
                        }

                        if (is_int((int)$day)) {
                            return $query->passDays($day);
                        }

                        return $query;
                    }
                ],
            ], $request)
            ->orderBy('id', 'desc')
            ->with('user:id,phone,first_name', 'service:name,id')
            ->withCount('promotions')
            ->paginate();

        $collection = $orders->toArray();
        $collection['sum_total_amount'] = $orders->sum('total_amount');
        return $collection;
    }

    public function show(Order $order)
    {
        $start = today()->subDays(7 - 1);
        $user = Auth::user();
        if ($user->isEmployee() && $order->created_at->lt($start)) {
            abort(403, 'You do not have right to perform this action');
        }
        $order->load('user:id,first_name,phone,last_name,middle_name', 'service:id,name', 'promotions:id,name,discount',
            'products', 'gcash');
        return $order;
    }

    public function store(OrderValidate $validation)
    {
        $logInUser = auth()->user();
        $data = $validation->validate();

        return tap(Order::create($data + ['creator_id' => $logInUser->id,]),
            function ($order) use ($validation) {

                if (property_exists($validation, 'promotions')) {
                    $qualifyPromotions = $validation->promotions;
                    OrderPromotion::insertByPromotions($qualifyPromotions, $order);
                }
                OrderCreated::dispatch($order, $validation->products);
            });
    }
}
