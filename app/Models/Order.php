<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'payment_method_id',
        'voucher_id',
        'total_price',
        'note',
    ];


    // Một order có thể có nhiều bàn (qua bảng trung gian order_tables)
    public function tables()
    {
        return $this->belongsToMany(Table::class, 'order_tables', 'order_id', 'table_id')
            ->withPivot(['reservation_date', 'reservation_time'])
            ->withTimestamps();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}