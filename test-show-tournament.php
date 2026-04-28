<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require __DIR__ . '/vendor/autoload.php';

    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    echo "=== KIỂM TRA BOOKING GIẢI ĐẤU ===\n\n";

    $tournamentId = 8;

    // 1. Lấy tất cả booking của giải đấu
    echo "1️⃣ Lấy bookings của tournament ID {$tournamentId}...\n";

    $bookings = App\Models\Book::where('tournament_id', $tournamentId)
        ->orderBy('day_book')
        ->orderBy('start_time')
        ->get();

    echo "✅ Tìm thấy: " . $bookings->count() . " bookings\n\n";

    if ($bookings->isEmpty()) {
        echo "❌ Không có booking nào!\n";
        exit;
    }

    // Hiển thị bookings theo ngày
    echo "📋 DANH SÁCH BOOKINGS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    foreach ($bookings as $b) {
        echo sprintf(
            "ID: %d | Field: %d | Date: %s | Time: %s-%s\n",
            $b->id,
            $b->field_id,
            $b->day_book->format('Y-m-d'),
            substr($b->start_time, 0, 5),
            substr($b->end_time, 0, 5)
        );
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // 2. Test các ngày khác nhau
    echo "2️⃣ KIỂM TRA CÁC NGÀY KHÁC NHAU:\n\n";

    $testFieldId = $bookings->first()->field_id;
    $testDates = ['2026-04-28', '2026-04-29', '2026-04-30', '2026-05-01'];

    foreach ($testDates as $date) {
        $count = App\Models\Book::where('field_id', $testFieldId)
            ->where('day_book', $date)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        if ($count > 0) {
            echo "❌ Ngày {$date}: Có {$count} booking(s)\n";
        } else {
            echo "✅ Ngày {$date}: TRỐNG - Có thể đặt được\n";
        }
    }

    echo "\n=== KẾT THÚC ===\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

// 1. Lấy tất cả booking của giải đấu
echo "1️⃣ BOOKINGS CỦA GIẢI ĐẤU ID {$tournamentId}:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$bookings = App\Models\Book::where('tournament_id', $tournamentId)
    ->orderBy('day_book')
    ->orderBy('start_time')
    ->get(['id', 'field_id', 'day_book', 'start_time', 'end_time', 'booking_type', 'status']);

if ($bookings->isEmpty()) {
    echo "❌ Không có booking nào cho giải đấu ID {$tournamentId}\n\n";
} else {
    echo "📋 Tổng số: " . $bookings->count() . " bookings\n\n";

    // Group theo ngày
    $groupedByDate = $bookings->groupBy(function($item) {
        return $item->day_book->format('Y-m-d');
    });

    foreach ($groupedByDate as $date => $dateBookings) {
        echo "📅 {$date} (" . count($dateBookings) . " bookings):\n";
        foreach ($dateBookings as $b) {
            echo sprintf(
                "   • ID: %-3d | Field: %-3d | %s - %s | %s\n",
                $b->id,
                $b->field_id,
                substr($b->start_time, 0, 5),
                substr($b->end_time, 0, 5),
                $b->booking_type
            );
        }
        echo "\n";
    }
}

// 2. Test API getBookedSlots cho các ngày khác nhau
echo "\n2️⃣ TEST API getBookedSlots CHO CÁC NGÀY KHÁC NHAU:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (!$bookings->isEmpty()) {
    $testFieldId = $bookings->first()->field_id;
    $testDates = ['2026-04-28', '2026-04-29', '2026-04-30'];

    foreach ($testDates as $testDate) {
        echo "📅 Ngày: {$testDate} (Field ID: {$testFieldId})\n";

        $bookedInDate = App\Models\Book::where('field_id', $testFieldId)
            ->where('day_book', $testDate)
            ->whereNotIn('status', ['cancelled'])
            ->get(['id', 'start_time', 'end_time', 'booking_type']);

        if ($bookedInDate->isEmpty()) {
            echo "   ✅ Không có booking nào - NGÀY NÀY TRỐNG!\n";
        } else {
            echo "   ❌ Có " . $bookedInDate->count() . " booking(s):\n";
            foreach ($bookedInDate as $b) {
                echo sprintf(
                    "      - ID: %d | %s - %s | %s\n",
                    $b->id,
                    substr($b->start_time, 0, 5),
                    substr($b->end_time, 0, 5),
                    $b->booking_type
                );
            }
        }
        echo "\n";
    }
}

// 3. Test check availability cho một slot cụ thể
echo "\n3️⃣ TEST CHECK AVAILABILITY:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (!$bookings->isEmpty()) {
    $testFieldId = $bookings->first()->field_id;

    // Test ngày có booking
    echo "Test 1: Ngày 2026-04-28 (có booking) - 06:00-12:00\n";
    $conflict1 = App\Models\Book::where('field_id', $testFieldId)
        ->where('day_book', '2026-04-28')
        ->whereNotIn('status', ['cancelled'])
        ->where(function ($query) {
            $query->whereBetween('start_time', ['06:00:00', '12:00:00'])
                ->orWhereBetween('end_time', ['06:00:00', '12:00:00'])
                ->orWhere(function ($q) {
                    $q->where('start_time', '<=', '06:00:00')
                        ->where('end_time', '>=', '12:00:00');
                });
        })
        ->first();

    if ($conflict1) {
        echo "   ❌ CONFLICT: " . substr($conflict1->start_time, 0, 5) . " - " . substr($conflict1->end_time, 0, 5) . "\n";
    } else {
        echo "   ✅ AVAILABLE\n";
    }

    // Test ngày không có booking
    echo "\nTest 2: Ngày 2026-04-29 (không có booking) - 06:00-12:00\n";
    $conflict2 = App\Models\Book::where('field_id', $testFieldId)
        ->where('day_book', '2026-04-29')
        ->whereNotIn('status', ['cancelled'])
        ->where(function ($query) {
            $query->whereBetween('start_time', ['06:00:00', '12:00:00'])
                ->orWhereBetween('end_time', ['06:00:00', '12:00:00'])
                ->orWhere(function ($q) {
                    $q->where('start_time', '<=', '06:00:00')
                        ->where('end_time', '>=', '12:00:00');
                });
        })
        ->first();

    if ($conflict2) {
        echo "   ❌ CONFLICT: " . substr($conflict2->start_time, 0, 5) . " - " . substr($conflict2->end_time, 0, 5) . "\n";
    } else {
        echo "   ✅ AVAILABLE - CÓ THỂ ĐẶT ĐƯỢC!\n";
    }
}

echo "\n=== KẾT THÚC KIỂM TRA ===\n";
    $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
    echo "   Cached payment_status: " . ($cached['payment_status'] ?? 'N/A') . "\n";
} else {
    echo "   ✅ Không có cache cho tournament này\n";
}

echo "\n=== KẾT THÚC TEST ===\n";
