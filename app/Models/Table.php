<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'size',
        'imgae',
        'max_guests',
        'status',
        'qr_token',
    ];

    // Sửa lại relationship đúng tên
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_tables');
    }

    public function orderTables()
    {
        return $this->hasMany(OrderTable::class);
    }
}