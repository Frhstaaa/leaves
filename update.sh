#!/bin/bash
# =========================================================================
# SGIN LEAVES - 1-CLICK UPDATE (FRONTEND & BACKEND) FOR LINUX / VPS
# =========================================================================

echo "========================================================================="
echo " SGIN LEAVES - 1-CLICK UPDATE (FRONTEND & BACKEND)"
echo "========================================================================="
echo ""

echo "📥 [1/6] Menarik kode terbaru dari GitHub..."
git pull origin main

echo ""
echo "📦 [2/6] Memperbarui dependency Composer..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
fi

echo ""
echo "🎨 [3/6] Memperbarui asset Frontend (npm run build)..."
if command -v npm &> /dev/null; then
    npm run build
fi

echo ""
echo "🗄️ [4/6] Menjalankan migrasi database..."
php artisan migrate --force

echo ""
echo "🧹 [5/6] Membersihkan dan menyusun cache Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "🔒 [6/6] Memperbaiki izin storage dan symlink..."
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan storage:link 2>/dev/null || true

echo ""
echo "========================================================================="
echo "🎉 PEMBARUAN FRONTEND & BACKEND SUKSES!"
echo "========================================================================="
