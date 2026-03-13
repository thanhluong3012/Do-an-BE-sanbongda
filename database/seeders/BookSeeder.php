<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use App\Models\Field;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy user member đầu tiên
        $customer = User::whereHas('role', function ($query) {
            $query->where('name_role', 'member');
        })->first();

        if (!$customer) {
            echo "Không tìm thấy member. Vui lòng chạy seeder users trước.\n";
            return;
        }

        // Lấy các sân có sẵn
        $fields = Field::where('status', 'available')->get();

        if ($fields->isEmpty()) {
            echo "Không tìm thấy sân khả dụng. Vui lòng chạy seeder fields trước.\n";
            return;
        }

        // Tạo một số booking mẫu
        $bookings = [
            [
                'user_id' => $customer->id,
                'field_id' => $fields->first()->id,
                'day_book' => '2026-02-27',
                'start_time' => '08:00',
                'end_time' => '10:00',
                'total_price' => 200000,
            ],
            [
                'user_id' => $customer->id,
                'field_id' => $fields->first()->id,
                'day_book' => '2026-02-27',
                'start_time' => '14:00',
                'end_time' => '16:00',
                'total_price' => 250000,
            ],
            [
                'user_id' => $customer->id,
                'field_id' => $fields->count() > 1 ? $fields[1]->id : $fields->first()->id,
                'day_book' => '2026-02-28',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'total_price' => 300000,
            ],
        ];

        foreach ($bookings as $booking) {
            Book::create($booking);
        }

        echo "Đã tạo " . count($bookings) . " booking mẫu thành công!\n";
    }
}
