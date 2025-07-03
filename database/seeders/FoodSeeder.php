<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodSeeder extends Seeder
{
    public function run()
    {
        DB::table('foods')->insert([
            [
                'category_id' => 1,
                'name' => 'Sushi Cá Hồi',
                'price' => 120000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'name' => 'Sashimi Bạch Tuộc',
                'price' => 135000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'name' => 'Tempura Tôm',
                'price' => 90000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'name' => 'Cơm cuộn California',
                'price' => 110000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'name' => 'Mì Udon Nước',
                'price' => 80000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'name' => 'Mì Ramen Cay',
                'price' => 95000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 3,
                'name' => 'Trà Xanh Matcha',
                'price' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 3,
                'name' => 'Nước Ép Dưa Hấu',
                'price' => 60000,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // 🔽 10 món mới bên dưới
            [
                'category_id' => 1,
                'name' => 'Sushi Lươn Nhật',
                'price' => 140000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'name' => 'Sushi Thanh Cua',
                'price' => 105000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'name' => 'Sashimi Cá Ngừ',
                'price' => 145000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'name' => 'Cơm Lươn Nhật',
                'price' => 125000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'name' => 'Mì Soba Lạnh',
                'price' => 85000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'name' => 'Soup Miso',
                'price' => 45000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 3,
                'name' => 'Nước Ép Cam',
                'price' => 55000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 3,
                'name' => 'Trà Đào Nhiệt Đới',
                'price' => 65000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 3,
                'name' => 'Kem Matcha Nhật',
                'price' => 70000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}