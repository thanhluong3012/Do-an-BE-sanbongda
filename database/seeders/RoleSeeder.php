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
            ],
            [
                'name_role' => 'owner',
                'description' => 'Chủ sở hữu của một khu vực sân bóng',
            ],
            [
                'name_role' => 'staff',
                'description' => 'Nhân viên thuộc khu vực sân bóng',
            ],
            [
                'name_role' => 'member',
                'description' => 'Người dùng đã đăng nhập vào hệ thống',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name_role' => $role['name_role']],
                [
                    'description' => $role['description'],
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }
    }
}
