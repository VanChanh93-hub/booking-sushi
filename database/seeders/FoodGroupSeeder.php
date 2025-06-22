<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('food_groups')->insert([
            [
                'category_id' => 2,
                'name' => 'Sashimi đơn lẻ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'name' => 'Bộ sưu tập Sashimi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 4,
                'name' => 'Cơm',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 4,
                'name' => 'Mì',
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'category_id' => 5,
                'name' => 'Nigiri cơ bản',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 5,
                'name' => 'Nigiri cao cấp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 5,
                'name' => 'Bộ sưu tập Nigiri',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'category_id' => 6,
                'name' => 'Đồ uống không có cồn',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 6,
                'name' => 'Bia & Rượu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'category_id' => 6,
                'name' => 'Rượu sake',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
