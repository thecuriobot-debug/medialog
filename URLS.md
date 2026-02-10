# MediaLog URLs - Quick Reference

## 🌐 Current Access Points

### **Production Server**
- **IP Address:** http://157.245.186.58/medialog/
- **Status:** ✅ Live and deployed
- **Server:** DigitalOcean Ubuntu (root@157.245.186.58)

### **Domain Status**
- **Domain:** 1n2.org
- **Status:** ⏳ Transfer in progress
- **Future URL:** http://1n2.org/medialog/ (once transfer completes)
- **DNS:** Will point to 157.245.186.58

### **Local Development**
- **URL:** http://localhost:8000/medialog/
- **Server:** PHP built-in server
- **Path:** /Users/curiobot/Sites/1n2.org/medialog/

---

## 📄 Page URLs

### Production (Current)
```
Dashboard:    http://157.245.186.58/medialog/
Books:        http://157.245.186.58/medialog/books.php
Movies:       http://157.245.186.58/medialog/movies.php
Authors:      http://157.245.186.58/medialog/authors.php
Directors:    http://157.245.186.58/medialog/directors.php
Stats:        http://157.245.186.58/medialog/stats.php
Insights:     http://157.245.186.58/medialog/insights.php
```

### Local Development
```
Dashboard:    http://localhost:8000/medialog/
Books:        http://localhost:8000/medialog/books.php
Movies:       http://localhost:8000/medialog/movies.php
Authors:      http://localhost:8000/medialog/authors.php
Directors:    http://localhost:8000/medialog/directors.php
Stats:        http://localhost:8000/medialog/stats.php
Insights:     http://localhost:8000/medialog/insights.php
```

---

## 🚀 Deployment Commands

### Deploy Single File
```bash
cd /Users/curiobot/Sites/1n2.org/medialog
scp [filename].php root@157.245.186.58:/var/www/html/medialog/
```

### Deploy Multiple Files
```bash
cd /Users/curiobot/Sites/1n2.org/medialog
scp *.php root@157.245.186.58:/var/www/html/medialog/
```

### Deploy Entire Project
```bash
cd /Users/curiobot/Sites/1n2.org/medialog
rsync -avz --exclude 'node_modules' --exclude '.git' ./ root@157.245.186.58:/var/www/html/medialog/
```

---

## 🗄️ Database Access

### Local MySQL
```bash
mysql -u root myapp_db
```

### Production MySQL (via SSH)
```bash
ssh root@157.245.186.58
mysql -u huntuser -p myapp_db
# Password: [your password]
```

---

## 📊 Current Stats (as of Feb 2026)

- **Books:** 782 total
- **Movies:** 1,708 total (after Letterboxd CSV import)
- **Total Media:** 2,490 items
- **Date Range:** 2011-2025 (14 years)
- **With Reviews:** 247 books

---

## 🔄 Domain Transfer Checklist

When 1n2.org transfer completes:

- [ ] Update DNS A record to point to 157.245.186.58
- [ ] Test http://1n2.org/medialog/
- [ ] Update documentation with new URL
- [ ] Configure SSL certificate (Let's Encrypt)
- [ ] Update any hardcoded URLs in code (if any)
- [ ] Update GitHub README with new URL

---

## 🛠️ Server Info

**SSH Access:**
```bash
ssh root@157.245.186.58
```

**Web Root:**
```
/var/www/html/medialog/
```

**Apache/Nginx:**
- Check config at `/etc/apache2/` or `/etc/nginx/`

**PHP Version:**
```bash
php -v
```

**Restart Web Server:**
```bash
# Apache
sudo systemctl restart apache2

# Nginx
sudo systemctl restart nginx
```

---

## 🔗 Repository

**GitHub:** https://github.com/thecuriobot-debug/medialog

**Clone Command:**
```bash
git clone https://github.com/thecuriobot-debug/medialog.git
```

---

**Last Updated:** February 9, 2026
