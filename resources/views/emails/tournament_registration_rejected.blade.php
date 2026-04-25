<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Từ chối đăng ký giải đấu</title>
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
            background-color: #dc3545;
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
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .reason-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
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
            <h2>❌ Đăng ký giải đấu bị từ chối</h2>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $captainName }}</strong>,</p>

            <p>Rất tiếc, đơn đăng ký tham gia giải đấu của đội <strong>{{ $teamName }}</strong> đã bị từ chối.</p>

            <div class="info-box">
                <p><strong>📋 Thông tin đăng ký:</strong></p>
                <ul>
                    <li><strong>Tên giải:</strong> {{ $tournamentName }}</li>
                    <li><strong>Đội tham dự:</strong> {{ $teamName }}</li>
                </ul>
            </div>

            <div class="reason-box">
                <p><strong>📝 Lý do từ chối:</strong></p>
                <p>{{ $rejectedReason }}</p>
            </div>

            <p><strong>📌 Bạn có thể:</strong></p>
            <ul>
                <li>🔍 Xem lại yêu cầu của giải đấu</li>
                <li>✏️ Khắc phục vấn đề theo lý do từ chối</li>
                <li>🔄 Đăng ký lại nếu vẫn còn hạn và đã đáp ứng đủ điều kiện</li>
                <li>📞 Liên hệ BTC nếu có thắc mắc</li>
            </ul>

            <p>Cảm ơn bạn đã quan tâm đến giải đấu!</p>

            <p>Trân trọng,<br><strong>{{ config('app.name') }}</strong></p>
        </div>

        <div class="footer">
            <p>Email này được gửi tự động, vui lòng không trả lời.</p>
        </div>
    </div>
</body>
</html>
