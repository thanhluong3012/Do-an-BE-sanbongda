<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Mail\OwnerRegistrationApprovedMail;
use App\Mail\OwnerRegistrationRejectedMail;
use Illuminate\Support\Facades\Mail;

echo "=== TEST EMAIL BREVO ===\n\n";

// Test 1: Gửi email approve
echo "Test 1: Gửi email APPROVED...\n";
try {
    Mail::to('luongtidai@gmail.com')->send(
        new OwnerRegistrationApprovedMail(
            'Nguyễn Văn A',
            'Cụm Sân ABC'
        )
    );
    echo "✅ Email APPROVED đã gửi thành công đến luongtidai@gmail.com\n\n";
} catch (\Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n\n";
}

// Test 2: Gửi email reject
echo "Test 2: Gửi email REJECTED...\n";
try {
    Mail::to('luongtidai@gmail.com')->send(
        new OwnerRegistrationRejectedMail(
            'Nguyễn Văn B',
            'Cụm Sân XYZ',
            'Thông tin không đầy đủ, vui lòng cung cấp thêm giấy tờ'
        )
    );
    echo "✅ Email REJECTED đã gửi thành công đến luongtidai@gmail.com\n\n";
} catch (\Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n\n";
}

echo "=== HOÀN THÀNH ===\n";
echo "Kiểm tra hộp thư: luongtidai@gmail.com\n";
echo "Lưu ý: Kiểm tra cả thư mục SPAM/JUNK\n";
