<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'description', 'status'];

    public function foods()
    {
        return $this->hasMany(Food::class);
    }
}
