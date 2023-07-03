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

        if ($request->has('phone')) {
            $query->whereHas('user', function (Builder $query) use ($request) {
                return $query->where('phone', $request->phone);
            });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('first_name')) {
            $query->whereHas('user', function (Builder $query) use ($request) {
                return $query->where('first_name', $request->first_name);
            });
        }

        if ($request->has('include_user') && $request->include_user == false) {
            $query->whereNull('user_id');
        }

        if ($request->has('include_user') && $request->include_user == true) {
            $query->whereNotNull('user_id');
        }

        if ($user->isEmployee()) {
            $query->where('creator_id', $user->id);
        }

        $orders = $query
            ->orderBy('id', 'desc')
            ->with('user:id,phone,first_name', 'service:name,id')
            ->withCount('promotions')
            ->get();

        return $orders;
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
