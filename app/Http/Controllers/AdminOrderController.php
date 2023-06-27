<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Promotion;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminOrderController extends Controller
{
    public function store(Request $request)
    {
        //front end can pass an array of promotion ids
        //we need to validate all promotions ids (it must be available promotions)
        //if promotions id is present they must provide the user_id otherwise validation exception
        //if a flag is sent telling that promotion is isolated multiple promotions is not allow

        //if user can avail all the promotions apply discount for them

        $user = auth()->user();
        if ($user->isCustomer()) {
            abort(403, 'You do not have right to perform this action');
        }

        $data = $request->validate([
            'amount' => 'nullable|decimal:0,4',
            'user_id' => 'nullable|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'promotion_ids' => 'nullable|array|exists:promotions,id',
            'isolated' => 'nullable|in:0,1|required:promotion_ids',
        ]);

        if ($request->has('promotion_ids')) {
            $query = Promotion::where('status', 1)->where(function ($query) {
                return $query->whereNull('until')->orWhere('until', '>', now());
            })->whereIn('id', $request->get('promotion_ids'));

            $query = $query->where('isolated', $request->get('isolated'));
            $promotions = $query->get();

            if (count($promotions) != count($request->get('promotion_ids'))) {
                throw ValidationException::withMessages([
                    'promotion_ids' => ['promotions are invalid']
                ]);
            }

            foreach ($promotions as $promotion) {
                $class = $promotion['class'];
                if (!class_exists($class)) {
                    throw ValidationException::withMessages([
                        'promotion_ids' => ['promotion is implemented']
                    ]);
                }
            }
        }

        $service = Service::find($data['service_id']);
        $data['amount'] ??= $service->price;
        return Order::create($data + ['creator_id' => $user->id]);
    }
}
