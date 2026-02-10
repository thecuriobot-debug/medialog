# MediaLog Deployment Guide

## 🚀 Deployment Workflow

MediaLog uses a **LOCAL → GIT → DROPLET** deployment workflow:

```
┌─────────┐     git push      ┌────────┐     git pull     ┌─────────┐
│  LOCAL  │ ─────────────────> │  GIT   │ ──────────────> │ DROPLET │
│  /Sites │                    │ GitHub │                  │ Server  │
└─────────┘                    └────────┘                  └─────────┘
```

---

## 📋 Quick Deploy (Automated)

**Use the deployment script:**

```bash
cd /Users/curiobot/Sites/1n2.org/medialog
./deploy.sh
```

This script will:
1. ✅ Add and commit your local changes
2. ✅ Push to GitHub
3. ✅ Pull latest changes on production server
4. ✅ Set proper permissions
5. ✅ Show deployment summary

---

## 🔧 Manual Deploy Steps

### 1. Local → GitHub

```bash
cd /Users/curiobot/Sites/1n2.org/medialog
git add -A
git commit -m "Your commit message"
git push origin main
```

### 2. GitHub → Droplet

```bash
ssh root@157.245.186.58
cd /var/www/html/medialog
git pull origin main
chmod -R 755 .
chown -R www-data:www-data .
exit
```

---

## 🗂️ Server Configuration

**Production Server:**
- Host: `157.245.186.58`
- Path: `/var/www/html/medialog`
- User: `root`
- Web Server: Apache2

**Git Configuration:**
- Remote: `https://github.com/thecuriobot-debug/medialog.git`
- Branch: `main`
- Protocol: HTTPS (no SSH keys needed)

**Database:**
- Host: `localhost`
- Database: `myapp_db`
- User: `root`

---

## 📊 Verification

After deployment, verify:

1. **Files are updated:**
   ```bash
   ssh root@157.245.186.58 "cd /var/www/html/medialog && git log -1 --oneline"
   ```

2. **Website is working:**
   - Visit: http://157.245.186.58/medialog/

3. **Check for errors:**
   ```bash
   ssh root@157.245.186.58 "tail -20 /var/log/apache2/error.log"
   ```

---

## ⚠️ Important Notes

**NEVER edit files directly on the server!**
- Always edit locally
- Commit to Git
- Deploy via script or pull

**Database changes require manual execution:**
- Schema changes (ALTER TABLE)
- Data population scripts
- Must be run directly on server

**File permissions:**
- Files: `755`
- Owner: `www-data:www-data`
- Scripts: `+x` (executable)

---

## 🔄 Rollback

If something goes wrong:

```bash
ssh root@157.245.186.58
cd /var/www/html/medialog
git log --oneline -10  # Find the good commit
git reset --hard COMMIT_HASH
```

---

## 📝 Deployment Checklist

Before deploying:
- [ ] Test changes locally
- [ ] Check PHP syntax: `php -l file.php`
- [ ] Verify database queries
- [ ] Update documentation if needed
- [ ] Commit with descriptive message

After deploying:
- [ ] Verify site loads
- [ ] Test modified functionality
- [ ] Check browser console for errors
- [ ] Test on mobile if UI changes made

---

## 🆘 Troubleshooting

**"Permission denied" on git pull:**
```bash
ssh root@157.245.186.58 "cd /var/www/html/medialog && git stash"
```

**"Your local changes would be overwritten:"**
```bash
ssh root@157.245.186.58 "cd /var/www/html/medialog && git reset --hard origin/main"
```

**White screen / PHP errors:**
```bash
ssh root@157.245.186.58 "tail -50 /var/log/apache2/error.log"
```

---

## 📂 Key Files

**Deployment:**
- `deploy.sh` - Automated deployment script
- `DEPLOYMENT.md` - This file

**Configuration:**
- `config.php` - Database credentials (not in Git)
- `.htaccess` - Apache configuration

**Scripts:**
- `scripts/populate-movie-metadata.php` - Movie data population

---

Last updated: February 10, 2026
