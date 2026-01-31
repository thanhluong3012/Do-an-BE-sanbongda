Phiên bản php: 8.4
laravel 12
lệnh cài JWT: composer require tymon/jwt-auth
Tạo file JWt trong config: php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
 lệnh tạo bảng field: php artisan make:migration create_fields_table
 lệnh tạo model: php artisan make:model Field
