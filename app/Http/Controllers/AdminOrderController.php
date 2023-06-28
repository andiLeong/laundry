<?php

namespace App\Http\Controllers;

use App\Http\Validation\AdminCreateOrderValidation;
use App\Models\Order;
use App\Models\OrderPromotion;

class AdminOrderController extends Controller
{
    public function store(AdminCreateOrderValidation $validation)
    {
        $logInUser = auth()->user();
        if ($logInUser->isCustomer()) {
            abort(403, 'You do not have right to perform this action');
        }

        $data = $validation->validate();
        $service = $validation->service;
        if ($validation->request->has('promotion_ids')) {

            unset($data['isolated']);
            unset($data['promotion_ids']);

            $qualifyPromotions = $validation->promotions;
//            if($qualifyPromotions->isEmpty()){
//                abort(403, 'Sorry You are not entitled with these promotions');
//            }

            $data['amount'] = $service->applyDiscount($qualifyPromotions->sum->getDiscount());
            return tap(Order::create($data + ['creator_id' => $logInUser->id]), function ($order) use ($qualifyPromotions) {
                OrderPromotion::insertByPromotions($qualifyPromotions, $order);
            });
        }

        $data['amount'] ??= $service->price;
        return Order::create($data + ['creator_id' => $logInUser->id]);
    }
}
