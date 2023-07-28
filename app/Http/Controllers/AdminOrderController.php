<?php

namespace App\Http\Controllers;

use App\Http\Validation\AdminCreateOrderValidation;
use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\Product;
use App\Models\ProductOrder;
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
            ->paginate();

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
        if ($validation->shouldValidatePromotionIds()) {

            $qualifyPromotions = $validation->promotions;

            $data['amount'] = $validation->service->applyDiscount($qualifyPromotions->sum->getDiscount());
            return tap(Order::create($data + ['creator_id' => $logInUser->id]),
                function ($order) use ($qualifyPromotions) {
                    OrderPromotion::insertByPromotions($qualifyPromotions, $order);
                });
        }

        $products = null;
        if ($validation->request->has('product_ids')) {
            $productIds = $validation->request->get('product_ids');
            $products = Product::whereIn('id', array_column($productIds, 'id'))->get();
        }
        unset($data['product_ids']);

        $order = Order::create($data + [
                'creator_id' => $logInUser->id,
                'total_amount' => $products ? $data['amount'] + $products->sum('price') : $data['amount'],
                'product_amount' => $products ? $products->sum('price') : 0
            ]);


//        dump($validation->request->get('product_ids'));

        if ($products) {
            $products->each(function ($product, $key) use ($order, $validation) {
                $quantity = array_key_exists('quantity', $validation->request->get('product_ids')[$key])
                    ? $validation->request->get('product_ids')[$key]['quantity']
                    : 1;

                ProductOrder::create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'quantity' => $quantity
                ]);
//                dump($key);
//                dump($quantity);
                $product->decrement('stock', $quantity);
            });
        }
        return $order;
    }
}
