<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{

    protected $fillable = [
        'name',
        'name_en',
        'price',
        'image',
        'description',
        'status',
    ];
    public function comboitems()
    {
        return $this->hasMany(ComboItem::class);
    }
}
