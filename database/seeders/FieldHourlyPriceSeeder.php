<?php

namespace Database\Seeders;

use App\Models\FieldCluster;
use App\Models\FieldHourlyPrice;
use Illuminate\Database\Seeder;

class FieldHourlyPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clusters = FieldCluster::orderBy('id')->get();

        if ($clusters->isEmpty()) {
            $this->command->error('Không có field cluster. Hãy chạy FieldClusterSeeder trước.');
            return;
        }

        foreach ($clusters as $cluster) {
            $priceMap = [
                'weekday' => [
                    '5v5' => 300000,
                    '7v7' => 450000,
                    '11v11' => 800000,
                ],
                'weekend' => [
                    '5v5' => 380000,
                    '7v7' => 550000,
                    '11v11' => 950000,
                ],
            ];

            foreach ($priceMap as $dayType => $pricesByFieldType) {
                foreach ($pricesByFieldType as $fieldType => $priceHour) {
                    FieldHourlyPrice::updateOrCreate(
                        [
                            'field_cluster_id' => $cluster->id,
                            'day_type' => $dayType,
                            'field_type' => $fieldType,
                        ],
                        [
                            'price_hour' => $priceHour,
                        ]
                    );
                }
            }
        }

        $this->command->info('Tạo dữ liệu mẫu field_hourly_prices thành công!');
    }
}
