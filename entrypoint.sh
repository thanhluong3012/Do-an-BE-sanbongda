#!/bin/sh

# Clear cache Laravel
php artisan config:clear
php artisan cache:clear

# Chạy Nginx ngầm
service nginx start

# Chạy PHP-FPM ở foreground (tiến trình chính)
exec php-fpm
