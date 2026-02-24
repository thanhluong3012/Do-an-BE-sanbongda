<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tìm role admin
        $adminRole = Role::where('name_role', 'admin')->first();

        if (!$adminRole) {
            $this->command->error('Role admin không tồn tại! Chạy RoleSeeder trước.');
            return;
        }

        // Tạo hoặc cập nhật tài khoản admin
        User::updateOrCreate(
            ['email' => 'admin@sanbongda.com'],
            [
                'role_id' => $adminRole->id,
                'name' => 'Admin System',
                'phone' => '0900000000',
                'status' => true,
                'password' => Hash::make('admin123'), // Password: admin123
            ]
        );

        $this->command->info('Tạo tài khoản admin thành công!');
        $this->command->info('Email: admin@sanbongda.com');
        $this->command->info('Password: admin123');
    }
}
