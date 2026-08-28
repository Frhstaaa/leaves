@echo off
title SGIN Leaves - 1-Click Update (Frontend + Backend)
color 0A
echo =========================================================================
echo  SGIN LEAVES - 1-CLICK UPDATE (FRONTEND & BACKEND)
echo =========================================================================
echo.

echo [1/6] Menarik kode terbaru dari GitHub (git pull)...
git pull origin main

echo.
echo [2/6] Memperbarui dependency Composer...
call composer install --no-dev --optimize-autoloader

echo.
echo [3/6] Memperbarui asset Frontend (npm run build)...
call npm.cmd run build

echo.
echo [4/6] Menjalankan migrasi database...
php artisan migrate --force

echo.
echo [5/6] Membersihkan dan menyusun ulang cache Laravel...
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo.
echo [6/6] Menghubungkan storage...
php artisan storage:link

echo.
echo =========================================================================
echo  PEMBARUAN FRONTEND & BACKEND SUKSES!
echo =========================================================================
pause
