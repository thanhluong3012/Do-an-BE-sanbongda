<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TournamentSessionPrice;
use App\Models\FieldCluster;

class TournamentPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy tất cả field clusters
        $fieldClusters = FieldCluster::all();

        if ($fieldClusters->isEmpty()) {
            $this->command->warn('⚠️ Không có field cluster nào trong database!');
            return;
        }

        // Danh sách loại sân
        $fieldTypes = ['5v5', '7v7', '11v11'];

        // Danh sách session types
        $sessionTypes = ['morning', 'afternoon', 'evening', 'full_day'];

        // Bảng giá mẫu (có thể điều chỉnh)
        $prices = [
            '5v5' => [
                'weekday' => [
                    'morning' => 500000,
                    'afternoon' => 600000,
                    'evening' => 800000,
                    'full_day' => 1800000,
                ],
                'weekend' => [
                    'morning' => 700000,
                    'afternoon' => 800000,
                    'evening' => 1000000,
                    'full_day' => 2300000,
                ],
            ],
            '7v7' => [
                'weekday' => [
                    'morning' => 700000,
                    'afternoon' => 800000,
                    'evening' => 1000000,
                    'full_day' => 2300000,
                ],
                'weekend' => [
                    'morning' => 900000,
                    'afternoon' => 1000000,
                    'evening' => 1200000,
                    'full_day' => 2800000,
                ],
            ],
            '11v11' => [
                'weekday' => [
                    'morning' => 1000000,
                    'afternoon' => 1200000,
                    'evening' => 1500000,
                    'full_day' => 3500000,
                ],
                'weekend' => [
                    'morning' => 1300000,
                    'afternoon' => 1500000,
                    'evening' => 1800000,
                    'full_day' => 4300000,
                ],
            ],
        ];

        $created = 0;
        $skipped = 0;

        // Tạo giá cho từng field cluster
        foreach ($fieldClusters as $cluster) {
            foreach ($fieldTypes as $fieldType) {
                foreach (['weekday', 'weekend'] as $dayType) {
                    foreach ($sessionTypes as $sessionType) {
                        $existing = TournamentSessionPrice::where([
                            'field_cluster_id' => $cluster->id,
                            'field_type' => $fieldType,
                            'day_type' => $dayType,
                            'session_type' => $sessionType,
                        ])->first();

                        if (!$existing) {
                            TournamentSessionPrice::create([
                                'field_cluster_id' => $cluster->id,
                                'field_type' => $fieldType,
                                'day_type' => $dayType,
                                'session_type' => $sessionType,
                                'price_per_session' => $prices[$fieldType][$dayType][$sessionType],
                            ]);
                            $created++;
                        } else {
                            $skipped++;
                        }
                    }
                }
            }
        }

        $this->command->info("✅ Đã tạo {$created} giá tournament mới");
        if ($skipped > 0) {
            $this->command->info("ℹ️ Bỏ qua {$skipped} giá đã tồn tại");
        }

        $this->command->info('');
        $this->command->info('📊 Tóm tắt:');
        $this->command->info("- Số field clusters: " . $fieldClusters->count());
        $this->command->info("- Loại sân: " . implode(', ', $fieldTypes));
        $this->command->info("- Session types: " . implode(', ', $sessionTypes));
        $this->command->info("- Tổng records: " . TournamentSessionPrice::count());
    }
}
