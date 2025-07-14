<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TableController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderTableController;
use App\Http\Controllers\CustomerVoucherController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ComboController;
use App\Http\Controllers\FoodgroupController;
use App\Http\Controllers\VNPayController;
use App\Http\Controllers\MoMoController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ChatController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/tables', [TableController::class, 'index']);        // List
Route::get('/tables/{id}', [TableController::class, 'show']);    // Detail
Route::post('/tables', [TableController::class, 'store']);       // Create
Route::put('/tables/{id}', [TableController::class, 'update']);  // Update
Route::delete('/tables/{id}', [TableController::class, 'destroy']); // Delete
Route::post('/tables/DateTime', [TableController::class, 'availableTimes']); // lấy ra g iờ trống của bàn theo ngày và số lượng người
Route::get('/tables/token/{token}', [TableController::class, 'getTableInfo']);



// order
Route::get('/orders', [OrderController::class, 'index']);              // Lấy danh sách đơn đặt
Route::get('/orders/{id}', [OrderController::class, 'show']);          // Lấy chi tiết đơn
Route::put('/order/update-status/{id}', [OrderController::class, 'updateStatus']); // Cập nhật trạng thái
Route::delete('/order/delete/{id}', [OrderController::class, 'destroy']);    // Xoá đơn đặt
Route::put('/orderitems/update-status/{id}', [OrderItemController::class, 'updateStatus']);
Route::post('/orders/bookTables', [OrderController::class, 'bookTables']);
Route::get('/statsDashbroad', [OrderController::class, 'statsDashbroad']);
Route::get('/orders/history/{id}', [OrderController::class, 'orderHistory']);
Route::post('/orders/cancel/{id}', [OrderController::class, 'cancelOrder']);




// combo
Route::get('/combos', [ComboController::class, 'index']); // Lấy danh sách combo
Route::get('/combos/{id}', [ComboController::class, 'show']); // Lấy chi tiết combo
Route::post('/combo/insert-combos', [ComboController::class, 'store']); // Tạo mới combo
Route::put('/combo/update-combo/{id}', [ComboController::class, 'update']); // Cập nhật combo
Route::put('/combo/update-status/{id}', [ComboController::class, 'updateStatus']); // Cập nhật trạng thái combo
Route::post('/combo/add-comboemp', [ComboController::class, 'createComboemp']); // Thêm món ăn vào combo
Route::post('/combos/add-food-combo/{id}', [ComboController::class, 'addFoodCombo']);
Route::delete('/combos/remove-food-combo/{combo_id}/{food_id}', [ComboController::class, 'destroyFoodId']);


// food
Route::get('/foods', [FoodController::class, 'index']);
Route::get('foods/category/{categoryId}/groups', [FoodController::class, 'foodsByCategoryWithGroups']);
Route::post('/food/insert-food', [FoodController::class, 'store']);
Route::put('food-update/{id}', [FoodController::class, 'update']);
Route::get('/food/category/{id}', [FoodController::class, 'getFoodsByCategory']);
Route::put('/food/update-status/{id}', [FoodController::class, 'updateStatus']);


// foodgroup
Route::get('/foodgroups', [\App\Http\Controllers\FoodgroupController::class, 'index']);
Route::post('/foodgroup/insert-foodgroup', [\App\Http\Controllers\FoodgroupController::class, 'store']);
Route::put('/foodgroup/update-foodgroup/{id}', [\App\Http\Controllers\FoodgroupController::class, 'update']);





// category
Route::get('/category', [CategoryController::class, 'index']);
Route::post('insert-category', [CategoryController::class, 'store']);
Route::put('category-update/{id}', [CategoryController::class, 'update']);
Route::put('category/update-status/{id}', [CategoryController::class, 'updateStatus']);





// customer





Route::post('/login', [CustomerController::class, "login"]);
Route::post('/register', [CustomerController::class, "store"]);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [CustomerController::class, 'index']);
    Route::get('/logout', [CustomerController::class, 'destroy']);
    Route::put('/customers/{id}/role', [CustomerController::class, 'updateRole'])->name('customers.updateRole');
});

Route::get('admin/customers', [CustomerController::class, 'listAll']);
Route::put('customers/{id}/status', [CustomerController::class, 'lockUnlock']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);





// google auth

Route::get('auth/google/redirect', [GoogleController::class, 'redirect']);
Route::get('auth/google/callback', [GoogleController::class, 'callback']);




// voucher
Route::get('/voucher', [VoucherController::class, 'index']); // lấy all
Route::post('/voucher', [VoucherController::class, 'store']); // tạo mới
Route::get('/voucherForCustomer', [VoucherController::class, 'getVoucherforCustomer']); // lấy tất cả voucher cho khách hàng
Route::get('/voucher/{id}', [VoucherController::class, 'show']); // lấy chi tiết
Route::put('/voucher/{id}', [VoucherController::class, 'update']); // cập nhật
Route::delete('/voucher/{id}', [VoucherController::class, 'destroy']); // xoá

// Route::post('/exchangePoints', [CustomerVoucherController::class, 'exchangePoints']);
Route::post('/applyVoucher', [CustomerVoucherController::class, 'applyVoucher']);
Route::post('/themVoucherWheel', [CustomerVoucherController::class, 'store']);
Route::get('/getAllVoucherByUser/{id}', [CustomerVoucherController::class, 'index']);



Route::post('/table/info/{token}', [OrderTableController::class, 'getTableInfo']); // kiểm tra bàn
Route::post('/orderItem/add', [OrderItemController::class, 'addItem']);


Route::post('/orders/vnpay-url', [VNPayController::class, 'createurlvnpay']);
Route::get('/vnpay-return', [VNPayController::class, 'vnpayReturn'])->name('vnpay.callback');

Route::get('/getItemsByOrderId/{id}', [OrderItemController::class, 'getItemsByOrderId']);

//ai
Route::get('/recommendations/{customerId}', [RecommendationController::class, 'tasteProfile']);
Route::post('/chat', [ChatController::class, 'chat']);
