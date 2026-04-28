<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== KIỂM TRA BOOKINGS ===\n\n";

$bookings = App\Models\Book::where('tournament_id', 8)
    ->orderBy('day_book')
    ->get(['id', 'field_id', 'day_book', 'start_time', 'end_time', 'status']);

echo "Tổng số bookings: " . $bookings->count() . "\n\n";

foreach ($bookings as $b) {
    echo "ID: {$b->id} | Field: {$b->field_id} | Date: {$b->day_book} | Time: {$b->start_time}-{$b->end_time} | Status: {$b->status}\n";
}

echo "\n=== XONG ===\n";
