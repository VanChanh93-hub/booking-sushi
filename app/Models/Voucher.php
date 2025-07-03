<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'discount_value',
        'start_date',
        'end_date',
        'status',
        'usage_limit',
        'used',
        'is_personal',
        'required_total',
        'describe',
        'required_points',
    ];



    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}