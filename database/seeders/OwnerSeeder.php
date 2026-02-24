<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\OwnerProfile;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tìm role owner
        $ownerRole = Role::where('name_role', 'owner')->first();

        if (!$ownerRole) {
            $this->command->error('Role owner không tồn tại! Chạy RoleSeeder trước.');
            return;
        }

        // Tạo hoặc cập nhật Owner 1
        $owner1 = User::updateOrCreate(
            ['email' => 'owner1@sanbongda.com'],
            [
                'role_id' => $ownerRole->id,
                'name' => 'Nguyễn Văn Hùng',
                'phone' => '0901234567',
                'status' => true,
                'password' => Hash::make('owner123'),
            ]
        );

        OwnerProfile::updateOrCreate(
            ['user_id' => $owner1->id],
            [
                'business_name' => 'Sân bóng Thể Thao Hùng',
                'address' => '123 Đường Nguyễn Văn Linh, Quận 7, TP.HCM',
                'image_code' => 'gpkd_001.jpg',
                'code_id' => '079201001234',
            ]
        );

        // Tạo hoặc cập nhật Owner 2
        $owner2 = User::updateOrCreate(
            ['email' => 'owner2@sanbongda.com'],
            [
                'role_id' => $ownerRole->id,
                'name' => 'Trần Thị Mai',
                'phone' => '0907654321',
                'status' => true,
                'password' => Hash::make('owner123'),
            ]
        );

        OwnerProfile::updateOrCreate(
            ['user_id' => $owner2->id],
            [
                'business_name' => 'Sân bóng Mai Sport',
                'address' => '456 Đường Võ Văn Tần, Quận 3, TP.HCM',
                'image_code' => 'gpkd_002.jpg',
                'code_id' => '079199002345',
            ]
        );

        $this->command->info('✅ Tạo 2 tài khoản owner thành công!');
        $this->command->info('Owner 1 - Email: owner1@sanbongda.com | Password: owner123');
        $this->command->info('Owner 2 - Email: owner2@sanbongda.com | Password: owner123');
    }
}
