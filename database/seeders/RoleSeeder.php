<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name_role' => 'admin',
                'description' => 'Quản lý toàn bộ hệ thống',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_role' => 'owner',
                'description' => 'Chủ sở hữu của một khu vực sân bóng',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_role' => 'staff',
                'description' => 'Nhân viên thuộc khu vực sân bóng',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_role' => 'member',
                'description' => 'Người dùng đã đăng nhập vào hệ thống',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('roles')->insert($roles);
    }
}
