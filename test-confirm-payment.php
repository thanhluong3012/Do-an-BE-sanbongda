<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test update payment status
$tournament = App\Models\Tournament::find(8);

if (!$tournament) {
    echo "❌ Không tìm thấy tournament ID 8\n";
    exit;
}

echo "📋 TRƯỚC KHI CẬP NHẬT:\n";
echo "Payment Status: " . $tournament->payment_status . "\n";
echo "Payment Date: " . ($tournament->payment_date ?? 'NULL') . "\n";
echo "Updated At: " . $tournament->updated_at . "\n\n";

// Thử cập nhật
$tournament->payment_status = 'paid';
$tournament->payment_date = now();

echo "🔄 Đang lưu...\n";
$result = $tournament->save();

if ($result) {
    echo "✅ Save thành công! Result: " . ($result ? 'true' : 'false') . "\n\n";

    // Refresh để lấy data mới từ DB
    $tournament->refresh();

    echo "📋 SAU KHI CẬP NHẬT:\n";
    echo "Payment Status: " . $tournament->payment_status . "\n";
    echo "Payment Date: " . $tournament->payment_date . "\n";
    echo "Updated At: " . $tournament->updated_at . "\n";
} else {
    echo "❌ Save thất bại!\n";
}
