<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản nhân viên được tạo</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #1565c0; color: #fff; padding: 30px 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px 40px; color: #333; }
        .body p { line-height: 1.6; }
        .info-box { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td { padding: 6px 0; font-size: 14px; }
        .info-box td:first-child { color: #666; width: 150px; }
        .info-box td:last-child { font-weight: bold; color: #1a1a1a; word-break: break-all; }
        .btn-wrap { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; background: #1565c0; color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 16px; font-weight: bold; }
        .note { font-size: 13px; color: #888; border-top: 1px solid #eee; padding-top: 16px; margin-top: 20px; }
        .footer { background: #f8f9fa; padding: 16px 40px; text-align: center; font-size: 12px; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚽ {{ config('app.name') }}</h1>
            <p style="margin: 8px 0 0; opacity: 0.9;">Tài khoản nhân viên đã được tạo</p>
        </div>

        <div class="body">
            <p>Xin chào <strong>{{ $staffName }}</strong>,</p>
            <p>Bạn đã được <strong>{{ $ownerName }}</strong> thêm vào hệ thống với vai trò <strong>nhân viên quản lý sân bóng</strong>.</p>
            <p>Dưới đây là thông tin đăng nhập của bạn:</p>

            <div class="info-box">
                <table>
                    <tr>
                        <td>Email đăng nhập:</td>
                        <td>{{ $staffEmail }}</td>
                    </tr>
                    <tr>
                        <td>Mật khẩu:</td>
                        <td>{{ $staffPassword }}</td>
                    </tr>
                </table>
            </div>

            <div class="btn-wrap">
                <a href="{{ env('FRONTEND_URL', 'http://localhost:8080') }}/auth" class="btn">🔐 Đăng nhập ngay</a>
            </div>

            <div class="note">
                <p>⚠️ Vui lòng đổi mật khẩu ngay sau khi đăng nhập lần đầu để bảo mật tài khoản.</p>
                <p>Nếu bạn không mong đợi email này, vui lòng liên hệ với chúng tôi.</p>
            </div>
        </div>

        <div class="footer">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
