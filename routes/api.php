<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TableController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/tables', [TableController::class, 'index']);        // List
Route::get('/tables/{id}', [TableController::class, 'show']);    // Detail
Route::post('/tables', [TableController::class, 'store']);       // Create
Route::put('/tables/{id}', [TableController::class, 'update']);  // Update
Route::delete('/tables/{id}', [TableController::class, 'destroy']); // Delete

// routes/api.php
Route::get('/orders', [OrderController::class, 'index']);              // Lấy danh sách đơn đặt
Route::post('/reservation', [OrderController::class, 'store']);             // Tạo đơn đặt mới
Route::get('/available-times', [TableController::class, 'availableTimes']); // Lý tìm bàn phù hợp

// Chi tiết, cập nhật, xoá
Route::get('/orders/{id}', [OrderController::class, 'show']);          // Lấy chi tiết đơn
Route::patch('/updateStatus-order/{id}/status', [OrderController::class, 'updateStatus']); // Cập nhật trạng thái
Route::delete('/delete-order/{id}', [OrderController::class, 'destroy']);    // Xoá đơn đặt

// Lịch theo ngày
Route::get('/orders/date/{date}', [OrderController::class, 'getByDate']);  // Lấy đơn theo ngày

//  Gợi ý bàn
Route::get('/orders/suggest-table', [OrderController::class, 'suggestTable']); // Gợi ý bàn theo số khách

// menu
Route::get('/menu',[MenuController::class,'index']);
Route::post('insert-menu',[MenuController::class,'store']);
Route::put('menu/{id}',[MenuController::class,'update']);
Route::delete('menu/{id}',[MenuController::class,'destroy']);
// customer
Route::post('/login',[CustomerController::class,"login"]);
Route::post('/register',[CustomerController::class,"store"]);
Route::middleware('auth:sanctum') ->group(function (){
    Route::get('/user', [CustomerController::class, 'index']);
    Route::get('/logout', [CustomerController::class, 'destroy']);
});

// login Google


Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

Route::get('auth/google/redirect', [GoogleController::class, 'redirect']);
Route::get('auth/google/callback', [GoogleController::class, 'callback']);
