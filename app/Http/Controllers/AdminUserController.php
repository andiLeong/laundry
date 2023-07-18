<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->leftJoin('orders', 'orders.user_id', '=', 'users.id')
            ->select('users.*',
                DB::raw('max(orders.created_at) as last_order_date'),
                DB::raw('sum(orders.amount) as total_order_amount')
            )
            ->filters([
                'phone' => [],
                'first_name' => [],
                'last_name' => [],
                'order_amount_larger_than' => [
                    'clause' => 'having',
                    'operator' => '>',
                    'column' => 'total_order_amount',
                ],
                'last_order_this_month' => [
                    'clause' => 'havingBetween',
                    'between' => [today()->startOfMonth(), today()->endOfMonth()],
                    'column' => 'last_order_date',
                ],
            ], $request)
            ->groupBy('users.id')
            ->orderBy('users.id', 'desc');

        return $query->paginate();
    }

    public function show(User $user)
    {
        return $user;
    }
}
