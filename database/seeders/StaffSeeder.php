<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Staff;
use App\Models\OwnerProfile;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tìm role staff
        $staffRole = Role::where('name_role', 'staff')->first();

        if (!$staffRole) {
            $this->command->error('Role staff không tồn tại! Chạy RoleSeeder trước.');
            return;
        }

        // Lấy owner đầu tiên để gán staff
        $owner1 = OwnerProfile::first();
        $owner2 = OwnerProfile::skip(1)->first();

        if (!$owner1 || !$owner2) {
            $this->command->error('Không tìm thấy owner! Chạy OwnerSeeder trước.');
            return;
        }

        // Tạo hoặc cập nhật Staff 1 (thuộc Owner 1)
        $staff1 = User::updateOrCreate(
            ['email' => 'staff1@sanbongda.com'],
            [
                'role_id' => $staffRole->id,
                'name' => 'Lê Văn Tú',
                'phone' => '0912345678',
                'status' => true,
                'password' => Hash::make('staff123'),
            ]
        );

        Staff::updateOrCreate(
            ['user_id' => $staff1->id],
            [
                'owner_id' => $owner1->id,
                'code_id' => '079201005678',
                'avatar' => 'avatar_staff1.jpg',
            ]
        );

        // Tạo hoặc cập nhật Staff 2 (thuộc Owner 1)
        $staff2 = User::updateOrCreate(
            ['email' => 'staff2@sanbongda.com'],
            [
                'role_id' => $staffRole->id,
                'name' => 'Phạm Thị Lan',
                'phone' => '0923456789',
                'status' => true,
                'password' => Hash::make('staff123'),
            ]
        );

        Staff::updateOrCreate(
            ['user_id' => $staff2->id],
            [
                'owner_id' => $owner1->id,
                'code_id' => '079199006789',
                'avatar' => 'avatar_staff2.jpg',
            ]
        );

        // Tạo hoặc cập nhật Staff 3 (thuộc Owner 2)
        $staff3 = User::updateOrCreate(
            ['email' => 'staff3@sanbongda.com'],
            [
                'role_id' => $staffRole->id,
                'name' => 'Hoàng Minh Tuấn',
                'phone' => '0934567890',
                'status' => true,
                'password' => Hash::make('staff123'),
            ]
        );

        Staff::updateOrCreate(
            ['user_id' => $staff3->id],
            [
                'owner_id' => $owner2->id,
                'code_id' => '079201007890',
                'avatar' => 'avatar_staff3.jpg',
            ]
        );

        $this->command->info('✅ Tạo 3 tài khoản staff thành công!');
        $this->command->info('Staff 1 (Owner 1) - Email: staff1@sanbongda.com | Password: staff123');
        $this->command->info('Staff 2 (Owner 1) - Email: staff2@sanbongda.com | Password: staff123');
        $this->command->info('Staff 3 (Owner 2) - Email: staff3@sanbongda.com | Password: staff123');
    }
}
