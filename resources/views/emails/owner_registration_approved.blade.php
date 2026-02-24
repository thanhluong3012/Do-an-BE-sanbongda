<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt đăng ký chủ sân</title>
</head>
<body>
    <h2>Xin chào {{ $ownerName }},</h2>

    <p>Yêu cầu đăng ký chủ sân của bạn đã được duyệt thành công.</p>

    @if(!empty($businessName))
        <p><strong>Tên cụm sân/doanh nghiệp:</strong> {{ $businessName }}</p>
    @endif

    <p>Bạn có thể đăng nhập vào hệ thống để bắt đầu quản lý cụm sân và sân con.</p>

    <p>Trân trọng,<br>{{ config('app.name') }}</p>
</body>
</html>
