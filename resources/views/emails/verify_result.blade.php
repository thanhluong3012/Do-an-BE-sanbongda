<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.12); padding: 48px 40px; max-width: 480px; width: 100%; text-align: center; }
        .icon { font-size: 64px; margin-bottom: 20px; }
        h1 { font-size: 22px; margin-bottom: 12px; color: #1a1a1a; }
        p { font-size: 15px; color: #555; line-height: 1.6; margin-bottom: 24px; }
        .btn { display: inline-block; padding: 12px 32px; border-radius: 6px; font-size: 15px; font-weight: bold; text-decoration: none; color: #fff; }
        .btn-success { background: #1a73e8; }
        .btn-danger { background: #e53935; }
        .footer { margin-top: 32px; font-size: 12px; color: #aaa; }
    </style>
</head>
<body>
    <div class="card">
        @if($success)
            <div class="icon">✅</div>
            <h1 style="color: #1a73e8;">{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a href="{{ config('app.url') }}" class="btn btn-success">Đăng nhập ngay</a>
        @else
            <div class="icon">❌</div>
            <h1 style="color: #e53935;">{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a href="{{ config('app.url') }}" class="btn btn-danger">Về trang chủ</a>
        @endif
        <div class="footer">⚽ {{ config('app.name') }}</div>
    </div>
</body>
</html>
