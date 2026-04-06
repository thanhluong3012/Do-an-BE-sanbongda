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

        $this->command->info("Tạo fields cho {$clusters->count()} clusters...");

        $totalFields = 0;
        foreach ($clusters as $cluster) {
            // Tạo 2 sân 5v5
            $field1 = Field::updateOrCreate(
                [
                    'field_cluster_id' => $cluster->id,
                    'name' => 'Sân 5v5 (1)',
                ],
                [
                    'field_type' => '5v5',
                    'status' => 'available'
                ]
            );
            $totalFields++;

            $field2 = Field::updateOrCreate(
                [
                    'field_cluster_id' => $cluster->id,
                    'name' => 'Sân 5v5 (2)',
                ],
                [
                    'field_type' => '5v5',
                    'status' => 'available'
                ]
            );
            $totalFields++;

            // Tạo 1 sân 7v7
            $field3 = Field::updateOrCreate(
                [
                    'field_cluster_id' => $cluster->id,
                    'name' => 'Sân 7v7 (1)',
                ],
                [
                    'field_type' => '7v7',
                    'status' => 'available'
                ]
            );
            $totalFields++;

            // Tạo 1 sân 11v11
            $field4 = Field::updateOrCreate(
                [
                    'field_cluster_id' => $cluster->id,
                    'name' => 'Sân 11v11 (1)',
                ],
                [
                    'field_type' => '11v11',
                    'status' => 'maintenance'
                ]
            );
            $totalFields++;

            $this->command->info("  ✓ Cluster '{$cluster->name}': 4 sân");
        }

        $this->command->info("✅ Tạo tổng {$totalFields} fields thành công!");
        $this->command->info('Mỗi cluster có 4 sân: 2 sân 5v5, 1 sân 7v7, 1 sân 11v11');
    }
}
