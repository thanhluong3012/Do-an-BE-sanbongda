
#!/bin/sh
set -e

cd /var/www

# Tạo các thư mục cần thiết
mkdir -p storage/logs bootstrap/cache

# Cấp quyền cho Laravel
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Xóa cache cấu hình cũ
php artisan config:clear

# Khởi động Nginx
service nginx start

# Chạy PHP-FPM ở foreground
exec php-fpm
