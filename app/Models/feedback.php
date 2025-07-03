<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'order_id',
        'rating',
        'comment',
        'customer_id',
    ];
}