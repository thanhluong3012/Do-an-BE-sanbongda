<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customerRole = Role::where('name_role', 'member')->first();

        if (!$customerRole) {
            $this->command->error('Role member chưa tồn tại. Hãy chạy RoleSeeder trước!');
            return;
        }

        // Tạo 1 customer mẫu
        User::updateOrCreate(
            ['email' => 'customer@gmail.com'],
            [
                'role_id' => $customerRole->id,
                'name' => 'Khách hàng',
                'phone' => '0123456789',
                'status' => true,
                'password' => Hash::make('password123'),
            ]
        );

        $this->command->info('Tạo dữ liệu mẫu customer thành công!');
    }
}
