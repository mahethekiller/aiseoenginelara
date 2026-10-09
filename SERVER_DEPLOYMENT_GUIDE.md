# 🚀 Production Server Deployment Guide
### AI SEO Engine & Content Intelligence Platform (Laravel 12 + DaisyUI 5 + Tailwind 4)

This guide documents the exact step-by-step production deployment instructions for deploying the **AI SEO Engine** to a Linux production server (Ubuntu 22.04/24.04 LTS, Debian, or cPanel/Cloud VPS).

---

## 📋 1. Server Prerequisites

Ensure the following packages are installed on the server:

| Software | Minimum Version | Recommended Version |
| :--- | :--- | :--- |
| **Operating System** | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS / Debian 12 |
| **PHP** | PHP 8.2 | PHP 8.3 (with `php-fpm`) |
| **Web Server** | Nginx 1.18+ or Apache 2.4+ | Nginx (recommended for performance) |
| **Database** | MySQL 8.0+ or MariaDB 10.4+ | MySQL 8.0.36+ |
| **Composer** | Composer 2.2+ | Composer 2.7+ |
| **Node.js & npm** | Node 18.x | Node 20.x LTS |
| **Process Manager** | Supervisor or Systemd | Supervisor |

### Required PHP Extensions:
```bash
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl php8.3-xml \
php8.3-mbstring php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-soap git unzip curl
```

---

## 🛠️ 2. Step-by-Step Installation Walkthrough

### Step 1: Clone Repository
Clone the repository to the production web directory (e.g. `/var/www/aiseoengine`):
```bash
cd /var/www
git clone https://github.com/mahethekiller/aiseoenginelara.git aiseoengine
cd /var/www/aiseoengine
```

---

### Step 2: Configure Directory Permissions
Ensure the web server user (`www-data` on Ubuntu/Debian, `nginx` on RHEL/CentOS) has write permissions to `storage` and `bootstrap/cache`:
```bash
sudo chown -R www-data:www-data /var/www/aiseoengine
sudo find /var/www/aiseoengine -type f -exec chmod 644 {} \;
sudo find /var/www/aiseoengine -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/aiseoengine/storage /var/www/aiseoengine/bootstrap/cache
```

---

### Step 3: Install PHP Composer Dependencies
Install production dependencies without development packages:
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

---

### Step 4: Environment Configuration
Copy the `.env.example` file to `.env`:
```bash
cp .env.example .env
nano .env
```

Configure the following critical production parameters in `.env`:
```ini
APP_NAME="AI SEO Engine"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Connection
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_seo_engine_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_db_password

# Session & Cache (Use Redis in high-concurrency production if available)
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Dual-Currency Token Cost Baseline
USD_TO_INR_RATE=87.50
```

---

### Step 5: Generate Application Encryption Key
```bash
php artisan key:generate --force
```

---

### Step 6: Non-Destructive Database Migrations & Seeding
Execute database migrations and seed the initial roles, permissions, system prompt templates, and options:
```bash
# Run migrations safely without dropping any production data
php artisan migrate --force

# Seed essential system records (roles, permissions, prompt archetypes, admin user)
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --force
```

#### System Roles & Permissions Matrix:
| Role | Assigned Permissions | Purpose |
| :--- | :--- | :--- |
| `super_admin` | All permissions | Master system administrator |
| `admin` | All permissions | Agency administrator |
| `seo_specialist` | `track-ranks`, `view-rank-database`, `export-rank-data`, `manage-clients` | Dedicated SEO rank tracking, SERP audits & Agency Client profiles |
| `editor` | `manage-presets`, `generate-content`, `view-content`, `manage-clients`, `track-ranks`, `view-rank-database`, `export-rank-data`, `delete-rank-data` | Content editor & template manager |
| `writer` | `generate-content`, `view-content`, `track-ranks`, `view-rank-database`, `export-rank-data` | Content creator & rank researcher |
| `viewer` | `view-content`, `view-rank-database` | Read-only auditor (no scanning or deletion) |

> **Default Seeded Credentials:**
> - **Super Admin**: `admin@webaiseo.com` / `password123`
> - **SEO Specialist**: `seo@example.com` / `password`
> - **Admin User**: `admin@example.com` / `password`
> - **Editor User**: `editor@example.com` / `password`
> - **Viewer User**: `viewer@example.com` / `password`

---

### Step 7: Build Frontend Assets (Vite, Tailwind 4 & DaisyUI 5)
Compile CSS and JavaScript bundle locally on the server (zero remote CDNs):
```bash
npm ci
npm run build
```

---

### Step 8: Storage Symlink & Performance Caching
Link storage directory and compile configuration/routes for speed:
```bash
php artisan storage:link

# Cache config, routes, and views for optimal performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🌐 3. Web Server Virtual Host Configuration

### Option A: Nginx (Recommended)
Create `/etc/nginx/sites-available/aiseoengine.conf`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/aiseoengine/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php index.html;
    charset utf-8;

    # Maximum upload size for DOCX/PDF/Sitemap uploads
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; # Adjust PHP version socket if necessary
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and test configuration:
```bash
sudo ln -s /etc/nginx/sites-available/aiseoengine.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### Option B: Apache Configuration
If using Apache, verify `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo a2enmod headers
```

In your VirtualHost configuration (`/etc/apache2/sites-available/aiseoengine.conf`):
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/aiseoengine/public

    <Directory /var/www/aiseoengine/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/aiseoengine_error.log
    CustomLog ${APACHE_LOG_DIR}/aiseoengine_access.log combined
</VirtualHost>
```

---

## 🔒 4. SSL Certificate Setup (Let's Encrypt)

Obtain a free SSL certificate using Certbot:
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

---

## ⚡ 5. Background Queue Worker (Supervisor)

Long-running LLM generation and web rewriter jobs should run asynchronously via the queue worker.

Install and configure Supervisor:
```bash
sudo apt install -y supervisor
sudo nano /etc/supervisor/conf.d/aiseoengine-worker.conf
```

Paste the following configuration:
```ini
[program:aiseoengine-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/aiseoengine/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/aiseoengine/storage/logs/worker.log
stopwaitsecs=3600
```

Start the Supervisor worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start aiseoengine-worker:*
```

---

## ⏰ 6. Scheduled Cron Job (Sitemaps, Analytics & Log Retention)

Add Laravel's scheduler to the system cron:
```bash
sudo crontab -u www-data -e
```

Add this single line:
```cron
* * * * * cd /var/www/aiseoengine && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🚀 7. Automated One-Command Update Script (`deploy.sh`)

Create `/var/www/aiseoengine/deploy.sh` for zero-downtime subsequent updates:

```bash
#!/bin/bash
set -e

echo "🚀 Deploying updates for AI SEO Engine..."

# 1. Put application into maintenance mode
php artisan down --render="errors::503" --secret="aiseo-deploy-bypass"

# 2. Pull latest code from main
git pull origin main

# 3. Install/update Composer dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Run non-destructive database migrations
php artisan migrate --force

# 5. Build frontend assets
npm ci
npm run build

# 6. Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart background queue workers
php artisan queue:restart
sudo supervisorctl restart aiseoengine-worker:*

# 8. Bring application out of maintenance mode
php artisan up

echo "✅ Deployment completed successfully!"
```

Make it executable:
```bash
chmod +x /var/www/aiseoengine/deploy.sh
```

---

## 🩺 8. Post-Deployment Verification Checklist

- [ ] Visit `https://your-domain.com/login` and log in with your admin credentials.
- [ ] Go to **Settings & Presets** (`/settings`) and enter your API keys (**Gemini**, **OpenAI**, **Anthropic**, **DeepSeek**, **SerpApi**).
- [ ] In **Settings & Presets**, verify your **SerpApi Account & Credits** card connects and displays live monthly searches remaining.
- [ ] Visit **Rank Tracker** (`/rank-tracker`) and run a live Top 50 keyword scan to verify Google SERP parsing, batch grouping, and live credits telemetry.
- [ ] Visit **Rank Database** (`/rank-database`) to verify historical search audits, batch vs keyword toggle, batch inspection dialog, and 1-click Batch CSV streaming.
- [ ] Click **Sync Provider Models** on `/settings` to verify live outgoing HTTP connectivity to provider APIs.
- [ ] Go to **Agency Clients** (`/clients`) and add a client domain to verify the XML sitemap crawler.
- [ ] Go to **SEO Blog Creator** (`/blog-creator`) and generate a sample test article to verify full LLM generation.
- [ ] Go to **Admin Dashboard** (`/dashboard`) to verify telemetry metrics and charts render cleanly in both Dark and Light themes.
