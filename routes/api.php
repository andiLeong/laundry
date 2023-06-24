<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdminOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// customer ordering
// 1 customer can choose pickup date time for ordering, customer can make order himself (customer can opt in pay on delivery)
// 2 customer can manage their address (create/delete/modify)
// 3 when customer ordering they may choose what service they want.
// 4 only few places are allow to pickup/delivery (at least for right now) since pickup/delivery is free
// 5 implement logic to check what date/time is available for pickup

// promotion
// 1 implement promotions

// employee ordering
// 1 for walk-in customer, employee can make order/bulk order for him. (can apply promotion)

// future feature
// 1 employee management (payroll, work hour, punch in/out)
// 2 dashboard chart data showing
// 3 branch concept

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('admin/order', [AdminOrderController::class, 'store']);


    Route::post('/address', [AddressController::class, 'store']);
    Route::patch('/address/{address}', [AddressController::class, 'update']);
    Route::get('/address', [AddressController::class, 'index']);
    Route::delete('/address/{address}', [AddressController::class, 'destroy']);
});
