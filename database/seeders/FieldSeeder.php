<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Field;
use App\Models\FieldCluster;

class FieldSeeder extends Seeder
{
    public function run(): void
    {
        $clusters = FieldCluster::orderBy('id')->get();

        if ($clusters->isEmpty()) {
            $this->command->error('Không có field cluster. Hãy chạy FieldClusterSeeder trước.');
            return;
        }

        foreach ($clusters as $cluster) {
            Field::updateOrCreate(
                [
                    'field_cluster_id' => $cluster->id,
                    'field_type' => '5v5',
                ],
                ['status' => 'available']
            );

            Field::updateOrCreate(
                [
                    'field_cluster_id' => $cluster->id,
                    'field_type' => '7v7',
                ],
                ['status' => 'available']
            );

            Field::updateOrCreate(
                [
                    'field_cluster_id' => $cluster->id,
                    'field_type' => '11v11',
                ],
                ['status' => 'maintenance']
            );
        }
        $this->command->info('Tạo dữ liệu mẫu fields thành công!');
    }
}
