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

            $promotions = $validation->promotions;
            $qualifyPromotions = $promotions
                ->map(fn($promotion) => new $promotion['class']($validation->user, $service, $promotion))
                ->filter
                ->qualify();

            $discounts = $qualifyPromotions->sum->getDiscount();
            $data['amount'] = $service->price - $service->price * $discounts;

            unset($data['isolated']);
            unset($data['promotion_ids']);
            $order = Order::create($data + ['creator_id' => $logInUser->id]);

            foreach ($qualifyPromotions as $promo) {
                OrderPromotion::insert([
                    'order_id' => $order->id,
                    'promotion_id' => $promo->id
                ]);
            }
            return $order;
        }

        $data['amount'] ??= $service->price;
        return Order::create($data + ['creator_id' => $logInUser->id]);
    }
}
