<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận tài khoản</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #1a73e8; color: #fff; padding: 30px 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px 40px; color: #333; }
        .body p { line-height: 1.6; }
        .info-box { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td { padding: 6px 0; font-size: 14px; }
        .info-box td:first-child { color: #666; width: 140px; }
        .info-box td:last-child { font-weight: bold; color: #1a1a1a; word-break: break-all; }
        .btn-wrap { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; background: #1a73e8; color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 16px; font-weight: bold; }
        .btn:hover { background: #1557b0; }
        .note { font-size: 13px; color: #888; border-top: 1px solid #eee; padding-top: 16px; margin-top: 20px; }
        .footer { background: #f8f9fa; padding: 16px 40px; text-align: center; font-size: 12px; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚽ TM ARENA</h1>
            <p style="margin: 8px 0 0; opacity: 0.9;">Xác nhận tài khoản của bạn</p>
        </div>

        <div class="body">
            <p>Xin chào <strong>{{ $userName }}</strong>,</p>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>TM ARENA</strong>. Dưới đây là thông tin tài khoản của bạn:</p>

            <div class="info-box">
                <table>
                    <tr>
                        <td>Email đăng nhập:</td>
                        <td>{{ $userEmail }}</td>
                    </tr>
                    <tr>
                        <td>Mật khẩu:</td>
                        <td>{{ $userPassword }}</td>
                    </tr>
                </table>
            </div>

            <p>Để kích hoạt tài khoản và có thể đăng nhập, vui lòng nhấn nút bên dưới:</p>

            <div class="btn-wrap">
                <a href="{{ $verificationUrl }}" class="btn">✅ Xác nhận tài khoản</a>
            </div>

            <p>Hoặc sao chép đường dẫn sau vào trình duyệt:</p>
            <p style="font-size: 13px; word-break: break-all; color: #1a73e8;">{{ $verificationUrl }}</p>

            <div class="note">
                <p>⏰ Link xác nhận có hiệu lực trong <strong>24 giờ</strong> kể từ thời điểm đăng ký.</p>
                <p>🔒 Nếu bạn không thực hiện đăng ký này, hãy bỏ qua email này.</p>
                <p>⚠️ Vui lòng lưu lại mật khẩu ở trên và đổi mật khẩu ngay sau khi đăng nhập lần đầu.</p>
            </div>
        </div>

        <div class="footer">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
