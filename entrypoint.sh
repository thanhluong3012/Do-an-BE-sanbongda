#!/bin/sh

# Ép cấp quyền ghi toàn bộ cho thư mục storage và bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Xóa sạch cache cũ của Laravel
php artisan config:clear
php artisan cache:clear

# Khởi động Nginx và chạy PHP-FPM ở foreground
service nginx start
exec php-fpm
