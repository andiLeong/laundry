<?php

namespace App\Http\Controllers;

use App\Http\Validation\AdminCreateOrderValidation;
use App\Models\Order;
use App\Models\OrderPromotion;

class AdminOrderController extends Controller
{
    public function index()
    {
        $orders = Order::orderBy('id','desc')->get();
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
