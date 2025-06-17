<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\VNPayController;


Route::get('/', function () {
    return view('welcome');
});


Route::get('/vnpay-payment', [VnpayController::class, 'createPayment']);
Route::get('/vnpay-return', [VnpayController::class, 'vnpayReturn']);
