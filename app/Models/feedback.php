<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use App\Models\Order;
class feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'order_id',
        'rating',
        'comment',
        'customer_id',
    ];

    // Nếu muốn tắt tự động cập nhật created_at, updated_at thì bỏ comment dòng dưới:
    // public $timestamps = false;

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
