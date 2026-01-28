<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::USER,
                'description' => 'Người dùng thường - Có quyền cơ bản để sử dụng hệ thống'
            ],
            [
                'name' => Role::ADMIN,
                'description' => 'Quản trị viên - Quản lý người dùng và nội dung'
            ],
            [
                'name' => Role::SUPERADMIN,
                'description' => 'Quản trị cấp cao - Quản lý toàn bộ hệ thống bao gồm Admin'
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }

        $this->command->info('✅ Đã tạo 3 roles: user, admin, superadmin');
    }
}
