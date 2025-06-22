<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{

    protected $fillable = [
        'name',
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
