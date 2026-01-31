<?php

namespace Database\Seeders;

use App\Models\Field;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            [
                'name' => 'Sân bóng Thể Thao A',
                'type' => '5 người',
                'location' => '123 Nguyễn Trãi',
                'district' => 'Quận 1',
                'price' => 300000,
                'status' => 'available',
                'rating' => 4.5,
                'openTime' => '06:00:00',
                'closeTime' => '22:00:00',
                'image' => '/images/field1.jpg',
                'rule' => 'Không hút thuốc, giữ vệ sinh sạch sẽ',
                'features' => ['Đèn chiếu sáng', 'Phòng thay đồ', 'Bãi đỗ xe'],
            ],
            [
                'name' => 'Sân bóng Văn Minh',
                'type' => '7 người',
                'location' => '456 Lê Lợi',
                'district' => 'Quận 3',
                'price' => 500000,
                'status' => 'available',
                'rating' => 4.8,
                'openTime' => '06:00:00',
                'closeTime' => '23:00:00',
                'image' => '/images/field2.jpg',
                'rule' => 'Đặt cọc trước 30%, không hoàn tiền khi huỷ',
                'features' => ['Đèn chiếu sáng', 'Phòng thay đồ', 'Bãi đỗ xe', 'Quầy nước', 'Wifi'],
            ],
            [
                'name' => 'Sân bóng Hoàng Gia',
                'type' => '11 người',
                'location' => '789 Võ Văn Tần',
                'district' => 'Quận 5',
                'price' => 1000000,
                'status' => 'available',
                'rating' => 5.0,
                'openTime' => '05:00:00',
                'closeTime' => '23:30:00',
                'image' => '/images/field3.jpg',
                'rule' => 'Tuân thủ quy định về an toàn, không mang đồ uống có cồn',
                'features' => ['Đèn chiếu sáng', 'Phòng thay đồ cao cấp', 'Bãi đỗ xe rộng', 'Quầy nước', 'Wifi', 'Camera an ninh'],
            ],
        ];

        foreach ($fields as $field) {
            Field::create($field);
        }
    }
}
