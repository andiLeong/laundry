<?php

namespace App\Http\Controllers;

use App\Http\Validation\AdminCreateOrderValidation;
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
                'exclude_user' => [
                    'clause' => 'whereNull',
                    'column' => 'user_id',
                ],
                'include_user' => [
                    'clause' => 'whereNotNull',
                    'column' => 'user_id',
                ],
                'phone' => [
                    'clause' => 'whereHas',
                    'relationship' => 'user',
                ],
                'first_name' => [
                    'clause' => 'callBack',
                    'callback' => function (Builder $query, Request $request) {
                        $query->whereHas('user', function (Builder $query) use ($request) {
                            return $query->where('first_name', $request->first_name);
                        });
                    }
                ],
            ], $request)
            ->orderBy('id', 'desc')
            ->with('user:id,phone,first_name', 'service:name,id')
            ->withCount('promotions')
            ->get();

        return $orders;
    }

    public function show(Order $order)
    {
        $user = Auth::user();
        if ($user->isEmployee() && $order->creator_id !== $user->id) {
            abort(403, 'You do not have right to perform this action');
        }
        $order->load('user:id,first_name,phone', 'service:id,name', 'promotions:id,name');
        return $order;
    }

    public function store(AdminCreateOrderValidation $validation)
    {
        $logInUser = auth()->user();
        $data = $validation->validate();
        $service = $validation->service;
        if ($validation->request->has('promotion_ids')) {

            unset($data['isolated']);
            unset($data['promotion_ids']);

            $qualifyPromotions = $validation->promotions;

            $data['amount'] = $service->applyDiscount($qualifyPromotions->sum->getDiscount());
            return tap(Order::create($data + ['creator_id' => $logInUser->id]), function ($order) use ($qualifyPromotions) {
                OrderPromotion::insertByPromotions($qualifyPromotions, $order);
            });
        }

        $data['amount'] ??= $service->price;
        return Order::create($data + ['creator_id' => $logInUser->id]);
    }
}
