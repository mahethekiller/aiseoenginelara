#!/bin/bash
set -e

echo "🚀 Starting automated pull and update..."

# 1. Mark directory as safe for git
git config --global --add safe.directory $(pwd)

# 2. Pull latest code from GitHub
echo "📥 Pulling latest code from origin main..."
git pull origin main

# 3. Install/update Composer dependencies (production mode)
if [ -f "composer.json" ]; then
    echo "📦 Updating composer packages..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# 4. Run non-destructive database migrations safely
echo "🗄️ Running migrations..."
php artisan migrate --force

# 5. Optimize configuration and view caches
echo "⚡ Rebuilding Laravel performance caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart background queue worker if active
echo "🔄 Restarting background queue workers..."
php artisan queue:restart || true
if command -v supervisorctl &> /dev/null; then
    supervisorctl restart aiseoengine-worker:* 2>/dev/null || true
fi

echo "✅ Git pull and deployment completed successfully!"
