<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SendVerificationCodeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SignUpController;
use App\Http\Controllers\TwilioSmsWebhookController;
use App\Http\Controllers\UserQualifiedPromotionController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;


// customer ordering
// 1 customer can choose pickup date time for ordering, customer can make order himself (customer can opt in pay on delivery)
// 2 customer can manage their address (create/delete/modify)
// 3 when customer ordering they may choose what service they want.
// 4 only few places are allow to pickup/delivery (at least for right now) since pickup/delivery is free
// 5 implement logic to check what date/time is available for pickup

// employee ordering
// 1 for walk-in customer, employee can make order/bulk order for him. (can apply promotion)

// future feature
// 1 employee management (payroll, work hour, punch in/out)
// 2 dashboard chart data showing
// 3 branch concept

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/promotion', [PromotionController::class, 'index']);
Route::get('/promotion/{promotion}', [PromotionController::class, 'show']);
Route::get('/service', [ServiceController::class, 'index']);


Route::middleware('guest')->group(function () {
    Route::post('/signup', [SignUpController::class, 'store']);
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/verification', [VerificationController::class, 'store']);
    Route::post('/verification-code/send/{phone}', [SendVerificationCodeController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', function(Request $request){
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
    });

    Route::middleware('staff.only')->group(function () {
        Route::get('/admin/order', [AdminOrderController::class, 'index']);
        Route::get('/admin/order/{order}', [AdminOrderController::class, 'show']);
        Route::post('/admin/order', [AdminOrderController::class, 'store']);
        Route::get('/admin/user/qualified-promotion/{user}/{service}', [UserQualifiedPromotionController::class, 'index']);

        Route::get('/admin/user', [AdminUserController::class, 'index'])->middleware('admin.only');
        Route::get('/admin/user/{user:phone}', [AdminUserController::class, 'show']);
    });


    Route::post('/address', [AddressController::class, 'store']);
    Route::patch('/address/{address}', [AddressController::class, 'update']);
    Route::get('/address', [AddressController::class, 'index']);
    Route::delete('/address/{address}', [AddressController::class, 'destroy']);
});


Route::post('/twilio/sms', TwilioSmsWebhookController::class);
