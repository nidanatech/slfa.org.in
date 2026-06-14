# Centos 9 Stream & Google VM Deployment Plan

This guide provides a comprehensive step-by-step plan to host your Laravel project on a Google Cloud VM running CentOS 9 Stream with MySQL.

## Phase 1: Google VM Instance Setup
1. **Create Instance**:
   - Machine type: `e2-medium` (minimum recommended for Laravel + Vite builds).
   - OS: CentOS Stream 9.
   - Boot Disk: SSD (Standard or Balanced), 20GB+.
   - Networking: Allow HTTP/HTTPS traffic.
2. **Assign Static IP**: Reserve a static external IP address.
3. **Firewall Rules**: Ensure ports 80 (HTTP) and 443 (HTTPS) are open.

## Phase 2: Server Environment Preparation
Connect to your server via SSH and run:

### 1. Update System & Install Utilities
```bash
sudo dnf update -y
sudo dnf install -y git wget curl zip unzip policycoreutils-python-utils
```

### 2. Install Nginx
```bash
sudo dnf install -y nginx
sudo systemctl enable --now nginx
```

### 3. Install PHP 8.2 (via Remi Repository)
```bash
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
sudo dnf module reset php
sudo dnf module enable php:remi-8.2 -y
sudo dnf install -y php-fpm php-cli php-common php-mysqlnd php-gd php-mbstring php-xml php-opcache php-zip php-bcmath php-intl
sudo systemctl enable --now php-fpm
```

### 4. Install MySQL 8.0 (or MariaDB)
CentOS Stream 9 does not include `mysql-server` in default repositories. Additionally, the official MySQL GPG keys often expire.

**Option A: MySQL 8.0/8.4 (Official)**
```bash
# 1. Add MySQL Community Repo
sudo dnf install -y https://dev.mysql.com/get/mysql84-community-release-el9-1.noarch.rpm

# 2. Fix GPG Key Mismatch (Import both current and legacy keys)
sudo rpm --import https://repo.mysql.com/RPM-GPG-KEY-mysql-2022
sudo rpm --import https://repo.mysql.com/RPM-GPG-KEY-mysql-2023

# 3. Install MySQL Server (Use --nogpgcheck if the above still fails for 8.4.8)
sudo dnf install -y mysql-server --nogpgcheck

# 4. Start & Secure
sudo systemctl enable --now mysqld
sudo mysql_secure_installation
```

> [!IMPORTANT]
> MySQL 8.4.8 is a very recent release (Jan 2026). If you experience GPG errors even after importing keys, the `--nogpgcheck` flag in step 3 is the most reliable way to proceed on CentOS Stream 9.

**Option B: MariaDB (CentOS Native - STRONGLY RECOMMENDED)**
MariaDB is the "native" database for CentOS/RHEL. It is 100% compatible with Laravel/MySQL and avoids repository key issues.
```bash
sudo dnf install -y mariadb-server
sudo systemctl enable --now mariadb
sudo mariadb_secure_installation
```

### 5. Install Composer & Node.js
```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20
curl -sL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo dnf install -y nodejs
```

## Phase 3: Application Setup

### 1. Project Directory & Permissions
```bash
sudo mkdir -p /var/www/sydkic
sudo chown -R $USER:$USER /var/www/sydkic
```

### 2. Database Setup
```sql
-- Run: sudo mysql
CREATE DATABASE sydkic_db;
CREATE USER 'sydkic_user'@'localhost' IDENTIFIED BY 'StrongPassword';
GRANT ALL PRIVILEGES ON sydkic_db.* TO 'sydkic_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Nginx Configuration
Create `/etc/nginx/conf.d/sydkic.conf`:
```nginx
server {
    listen 80;
    server_name your_domain.com;
    root /var/www/sydkic/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Phase 4: GitHub Actions (CI/CD)

### Required GitHub Secrets
Go to **Settings > Secrets and variables > Actions** and add:
- `SERVER_HOST`: Your VM External IP.
- `SERVER_USER`: Usually `centos` or the username you use for SSH.
- `SSH_PRIVATE_KEY`: Private key to access the server.
- `TARGET_DIR`: `/var/www/sydkic`

## Phase 5: Permissions & SELinux (CentOS Specific)
CentOS uses SELinux by default, which can block Laravel from writing to storage.
```bash
sudo chown -R nginx:nginx /var/www/sydkic/storage /var/www/sydkic/bootstrap/cache
sudo chmod -R 775 /var/www/sydkic/storage /var/www/sydkic/bootstrap/cache

# Allow Nginx to write to storage
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/sydkic/storage(/.*)?"
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/sydkic/bootstrap/cache(/.*)?"
sudo restorecon -Rv /var/www/sydkic
```

## Phase 6: Background Jobs (Queue & Scheduler)

Laravel needs a worker to process queues and a cron job for the scheduler.

### 1. Scheduler (Cron)
Add the following to your crontab (`sudo crontab -e -u nginx`):
```cron
* * * * * cd /var/www/sydkic && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Queue Worker (Supervisor)
Install Supervisor to manage the queue worker:
```bash
sudo dnf install -y supervisor
sudo systemctl enable --now supervisord
```

Create `/etc/supervisord.d/sydkic-worker.ini`:
```ini
[program:sydkic-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sydkic/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=nginx
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sydkic/storage/logs/worker.log
stopwaitsecs=3600
```

Update and start:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sydkic-worker:*
```

## Phase 7: SSL (Let's Encrypt)
```bash
sudo dnf install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your_domain.com
```

---

### Final Checklist for GitHub Actions
1. Ensure `nginx` user has `sudo` access for `systemctl reload php-fpm` (as shown in `deploy.yml`) or run phpfpm as the deployment user.
2. In GitHub Repo Settings:
    - Add `SSH_PRIVATE_KEY`
    - Add `SERVER_HOST` (IP)
    - Add `SERVER_USER` (e.g., `centos`)
    - Add `TARGET_DIR` (e.g., `/var/www/sydkic`)

### Troubleshooting Common Issues

#### 1. Rsync "Operation not permitted"
If you see errors like `chgrp failed: Operation not permitted`, it's because `rsync` is trying to preserve group/owner information that the user doesn't have rights to change.
**Fix**: Ensure your `rsync` command (or the GitHub Actions `deploy.yml`) includes these flags:
`--no-owner --no-group --no-perms`

#### 2. SELinux Blocks (Permission Denied in Browser)
If your site shows 403 or 500 errors but file permissions look correct, SELinux might be blocking Nginx.
**Fix**: Run Phase 5 commands again to restore contexts.
