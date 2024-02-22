<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdminBulkOrderController;
use App\Http\Controllers\AdminExpenseController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminOrderStatController;
use App\Http\Controllers\AdminStatController;
use App\Http\Controllers\AdminUpdateOrderController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\GcashOrderController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogOutController;
use App\Http\Controllers\OnlineOrderStatusController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPaidRecordController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SalaryDetailController;
use App\Http\Controllers\SendVerificationCodeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShiftsController;
use App\Http\Controllers\SignUpController;
use App\Http\Controllers\TwilioSmsWebhookController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserQualifiedPromotionController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// customer ordering
// 1 customer can choose pickup date time for ordering, customer can make order himself (customer can opt in pay on delivery)
// 2 customer can manage their address (create/delete/modify)
// 3 when customer ordering they may choose what service they want. by default only 8kg service customer can choose
//once order received we will adjust the order amount based on the actual weight (or create new order based on the actual weight)
//update actual weight pic
//send new order notification to customer
//we charge delivery fee

// note: when we make promotion we have to note the discount is applying for the occurring order.

// 5 implement logic to check what date/time is available for pickup

// future feature
// 3 branch concept

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/product', [ProductController::class, 'index']);
Route::get('/promotion', [PromotionController::class, 'index']);
Route::get('/promotion/{slug}', [PromotionController::class, 'show']);
Route::get('/service', [ServiceController::class, 'index']);


Route::middleware('guest')->group(function () {
    Route::post('/signup', [SignUpController::class, 'store'])->middleware('spam.detection:notification');
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/verification', [VerificationController::class, 'store']);
    Route::post('/verification-code/send/{phone}', [SendVerificationCodeController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', LogOutController::class);

    Route::middleware('staff.only')->group(function () {

        Route::post('/admin/attendance', [AttendanceController::class, 'store']);
        Route::get('/admin/salary', [SalaryController::class, 'index']);
        Route::get('/admin/salary-detail/{id}', [SalaryDetailController::class, 'index']);
        Route::get('/admin/shifts', [ShiftsController::class, 'index']);
        Route::get('/admin/attendance', [AttendanceController::class, 'index']);
        Route::get('/admin/order-paid-record', [OrderPaidRecordController::class, 'index']);

        Route::patch('/admin/online-order/{id}', [OnlineOrderStatusController::class, 'update']);
        Route::post('/admin/gcash-order', [GcashOrderController::class, 'store']);
        Route::post('/admin/order-bulk', AdminBulkOrderController::class);
        Route::get('/admin/order', [AdminOrderController::class, 'index']);
        Route::get('/admin/order/{order}', [AdminOrderController::class, 'show']);
        Route::post('/admin/order', [AdminOrderController::class, 'store']);
        Route::patch('/admin/order/{id}', [AdminOrderController::class, 'update']);
        Route::patch('/admin/order/{id}/{column}', [AdminUpdateOrderController::class, 'update']);
        Route::get('/admin/user/qualified-promotion/{user}/{service}', [UserQualifiedPromotionController::class, 'index']);

        Route::get('/admin/user/{user:phone}', [AdminUserController::class, 'show']);
    });

    Route::middleware('admin.only')->group(function () {
        Route::get('/admin/user', [AdminUserController::class, 'index']);
        Route::get('/admin/stats', [AdminStatController::class, 'index']);
        Route::get('/admin/order-stats', [AdminOrderStatController::class, 'index']);
        Route::get('/admin/expense', [AdminExpenseController::class, 'index']);
    });


    Route::patch('/user/profile', UserProfileController::class);
    Route::patch('/user/password', UserPasswordController::class);
    Route::get('/order', [OrderController::class, 'index']);
    Route::post('/order', [OrderController::class, 'store'])->name('customer.order.create');
    Route::get('/order/{id}', [OrderController::class, 'show']);
    Route::post('/address', [AddressController::class, 'store']);
    Route::patch('/address/{address}', [AddressController::class, 'update']);
    Route::get('/address', [AddressController::class, 'index']);
    Route::delete('/address/{address}', [AddressController::class, 'destroy']);
});


Route::post('/twilio/sms', TwilioSmsWebhookController::class);
