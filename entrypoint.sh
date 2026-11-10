#!/bin/sh

# Cấp quyền ghi tuyệt đối cho thư mục storage và bootstrap/cache để tránh lỗi Permission denied
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Xóa sạch cache cấu hình cũ để Laravel tự nhận biến môi trường mới từ Render
php artisan config:clear
php artisan cache:clear

# Khởi động Nginx ngầm
service nginx start

# Chạy PHP-FPM ở tiến trình chính để giữ container luôn sống
exec php-fpm
