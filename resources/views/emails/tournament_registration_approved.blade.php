<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt đăng ký giải đấu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #28a745;
            color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✅ Đăng ký giải đấu đã được duyệt!</h2>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $captainName }}</strong>,</p>

            <p>Chúc mừng! Đơn đăng ký tham gia giải đấu của đội <strong>{{ $teamName }}</strong> đã được BTC duyệt thành công.</p>

            <div class="info-box">
                <p><strong>📋 Thông tin giải đấu:</strong></p>
                <ul>
                    <li><strong>Tên giải:</strong> {{ $tournamentName }}</li>
                    <li><strong>Đội tham dự:</strong> {{ $teamName }}</li>
                    <li><strong>Ngày bắt đầu dự kiến:</strong> {{ $startDate }}</li>
                </ul>
            </div>

            <p><strong>📌 Bước tiếp theo:</strong></p>
            <ul>
                <li>✅ Đăng ký của bạn đã được xác nhận</li>
                <li>⏳ Chờ BTC thông báo lịch thi đấu và thông tin thanh toán (nếu có)</li>
                <li>📱 Theo dõi thông báo trên hệ thống để cập nhật thông tin mới nhất</li>
            </ul>

            <p>Chúc đội bóng của bạn thi đấu thành công!</p>

            <p>Trân trọng,<br><strong>{{ config('app.name') }}</strong></p>
        </div>

        <div class="footer">
            <p>Email này được gửi tự động, vui lòng không trả lời.</p>
        </div>
    </div>
</body>
</html>
