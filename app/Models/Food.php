<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{

    protected $table = 'foods';  // Đảm bảo đúng tên bảng
    protected $fillable = [
        'category_id',
        'group_id',
        'name',
        'name_en',
        'jpName',
        'description',
        'description_en',
        'price',
        'status',
        'image',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function group()
    {
        return $this->belongsTo(FoodGroup::class, 'group_id');
    }
}
