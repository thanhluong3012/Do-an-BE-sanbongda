#!/bin/sh
set -e

cd /var/www

# Đảm bảo thư mục tồn tại và cấp quyền toàn diện cho www-data
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 777 storage bootstrap/cache

# Xóa sạch cache cấu hình cũ để nhận biến môi trường mới từ Render
php artisan config:clear
php artisan cache:clear

# Khởi động Nginx
service nginx start

# Chạy PHP-FPM ở foreground với user www-data để khớp quyền
exec php-fpm -R
