<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\VNPayController;
use App\Http\Controllers\OrderController;


Route::get('/', function () {
    return view('welcome');
});


Route::get('/vnpay-payment', [VnpayController::class, 'createPayment']);
Route::get('/vnpay-return', [VnpayController::class, 'vnpayReturn']);
Route::get('/orders/history/{id_customer}', [OrderController::class, 'orderHistory']);
Route::post('/orders/cancel/{order_id}', [OrderController::class, 'cancelOrder']);
