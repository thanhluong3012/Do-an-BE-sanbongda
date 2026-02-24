<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FieldCluster;
use App\Models\OwnerProfile;

class FieldClusterSeeder extends Seeder
{
    public function run(): void
    {
        $owners = OwnerProfile::orderBy('id')->get();

        if ($owners->count() < 2) {
            $this->command->error('Cần ít nhất 2 owner profile. Hãy chạy OwnerSeeder trước.');
            return;
        }

        FieldCluster::updateOrCreate(
            [
                'owner_id' => $owners[0]->id,
                'name' => 'Cụm sân Thể Thao Hùng - Cơ sở 1',
            ],
            [
                'address' => '123 Nguyễn Văn Linh, Quận 7, TP.HCM',
                'phone' => '0901234567',
                'description' => 'Cụm sân cỏ nhân tạo tiêu chuẩn, có bãi xe.',
                'image' => 'cluster_hung_cs1.jpg',
                'open_time' => '05:00:00',
                'close_time' => '23:00:00',
            ]
        );

        FieldCluster::updateOrCreate(
            [
                'owner_id' => $owners[1]->id,
                'name' => 'Cụm sân Mai Sport - Cơ sở 1',
            ],
            [
                'address' => '456 Võ Văn Tần, Quận 3, TP.HCM',
                'phone' => '0907654321',
                'description' => 'Cụm sân trung tâm, thuận tiện di chuyển.',
                'image' => 'cluster_mai_cs1.jpg',
                'open_time' => '06:00:00',
                'close_time' => '22:30:00',
            ]
        );

        $this->command->info('Tạo dữ liệu mẫu field_clusters thành công!');
    }
}
