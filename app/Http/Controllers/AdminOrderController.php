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
            $query->where('creator_id', $user->id);
        }

        $orders = $query
            ->filters([
                'user_id' => [],
                'description' => [],
                'paid' => [],
                'payment' => [],
                'confirmed' => [],
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
                            $start = today()->subDays($day - 1);
                            $end = today()->copy()->addDay();
                            return $query->createBetween($start, $end);
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
        $user = Auth::user();
        if ($user->isEmployee() && $order->creator_id !== $user->id) {
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
