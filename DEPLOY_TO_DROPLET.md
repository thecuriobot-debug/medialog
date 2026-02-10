# 🚀 Deploy Hunt HQ to DigitalOcean Droplet

## Quick Deployment Steps

### 1. Prepare Files for Upload
```bash
cd /Users/curiobot/Sites/1n2.org/hunt-hq
tar -czf hunt-hq.tar.gz *.php *.db *.md
```

### 2. Upload to Droplet
```bash
# Replace with your droplet IP
scp hunt-hq.tar.gz root@YOUR_DROPLET_IP:/var/www/html/
```

### 3. SSH into Droplet
```bash
ssh root@YOUR_DROPLET_IP
```

### 4. Extract and Setup
```bash
cd /var/www/html
tar -xzf hunt-hq.tar.gz
rm hunt-hq.tar.gz

# Set permissions
chown -R www-data:www-data /var/www/html
chmod 755 *.php
chmod 664 hunt-hq.db
```

### 5. Install Dependencies (if needed)
```bash
# PHP
apt update
apt install php php-sqlite3 php-curl php-xml php-mbstring

# Apache/Nginx config
# Make sure your web server is configured
```

### 6. Configure Database Path
Edit `config.php` on the droplet:
```php
// Change from relative to absolute path
return new PDO('sqlite:/var/www/html/hunt-hq.db');
```

### 7. Test
```
http://YOUR_DROPLET_IP/hunt-hq/
```

---

## Alternative: Git Deploy

### 1. On Local Machine
```bash
cd /Users/curiobot/Sites/1n2.org/hunt-hq
git init
git add .
git commit -m "Initial Hunt HQ deployment"
```

### 2. On Droplet
```bash
cd /var/www/html
git clone YOUR_REPO_URL hunt-hq
cd hunt-hq
chown -R www-data:www-data .
```

---

## Nginx Config Example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/hunt-hq;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

---

## What to Upload

**Essential Files:**
- ✅ All .php files (index, books, movies, stats, etc.)
- ✅ hunt-hq.db (SQLite database)
- ✅ config.php

**Optional:**
- 📝 .md documentation files
- 📝 Import scripts

**Don't Upload:**
- ❌ .DS_Store
- ❌ Local backups

---

## Quick Commands

```bash
# One-liner deploy
tar -czf hunt-hq.tar.gz *.php *.db config.php && \
scp hunt-hq.tar.gz root@YOUR_IP:/var/www/html/

# Then on droplet:
cd /var/www/html && tar -xzf hunt-hq.tar.gz && \
chown -R www-data:www-data . && chmod 755 *.php
```

What's your droplet IP or domain?
