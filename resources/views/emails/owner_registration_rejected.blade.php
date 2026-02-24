<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Từ chối đăng ký chủ sân</title>
</head>
<body>
    <h2>Xin chào {{ $ownerName }},</h2>

    <p>Rất tiếc, yêu cầu đăng ký chủ sân của bạn hiện tại chưa được duyệt.</p>

    @if(!empty($businessName))
        <p><strong>Tên cụm sân/doanh nghiệp:</strong> {{ $businessName }}</p>
    @endif

    <p><strong>Lý do từ chối:</strong> {{ $rejectedReason }}</p>

    <p>Bạn có thể cập nhật lại hồ sơ và gửi yêu cầu mới khi đã bổ sung đầy đủ thông tin.</p>

    <p>Trân trọng,<br>{{ config('app.name') }}</p>
</body>
</html>
