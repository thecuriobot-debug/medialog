# 🚀 Quick Start: Push MediaLog to GitHub

## ⚡ Fast Track (3 Steps)

### 1. Create GitHub Repository
Go to: https://github.com/new
- **Name:** `medialog`
- **Description:** `Modern media tracker combining Letterboxd + Goodreads | Built through human-AI collaboration`
- **Public** or Private
- **⚠️ DO NOT** check any initialization boxes
- Click "Create repository"

### 2. Connect & Push
```bash
cd /Users/curiobot/Sites/1n2.org/medialog

# Add remote (replace YOUR_USERNAME)
git remote add origin https://github.com/YOUR_USERNAME/medialog.git

# Push everything
git push -u origin main --tags
```

### 3. Done! ✅
Visit: `https://github.com/YOUR_USERNAME/medialog`

You should see:
- ✅ All files uploaded
- ✅ README displaying
- ✅ 5 releases (v1.0 - v5.0)

---

## 📊 What's Already Done

✅ Git repository initialized  
✅ All files committed  
✅ 5 version tags created:
  - v1.0 - Foundation & Data Integration
  - v2.0 - Advanced Analytics
  - v3.0 - Modern Homepage  
  - v4.0 - Directors & Metadata
  - v5.0 - MediaLog Rebrand (current)

✅ README.md created  
✅ LICENSE added (MIT)  
✅ .gitignore configured  
✅ config.example.php provided

---

## 🎯 After Pushing

### Update README
```bash
# Replace placeholder with your username
nano README.md
# Change "yourusername" to your actual GitHub username
# Save and exit

git add README.md
git commit -m "docs: update GitHub username"
git push
```

### Create Releases (Optional)
On GitHub:
1. Go to "Releases" → "Draft a new release"
2. Select tag: v5.0
3. Title: "Version 5.0 - MediaLog Rebrand"
4. Copy description from tag
5. Publish

Repeat for v1.0 - v4.0

### Add Topics
In repository settings, add topics:
```
php mysql letterboxd goodreads media-tracker 
analytics dashboard ai-assisted reading-tracker
```

---

## 🔧 Troubleshooting

**"Permission denied"**
```bash
# Generate SSH key if needed
ssh-keygen -t ed25519 -C "your_email@example.com"

# Add to GitHub: Settings → SSH Keys
cat ~/.ssh/id_ed25519.pub

# Use SSH URL instead
git remote set-url origin git@github.com:USERNAME/medialog.git
```

**"Repository not found"**
- Check repository name matches exactly
- Verify you created the repository on GitHub first
- Make sure username is correct (case-sensitive)

**"Updates were rejected"**
```bash
# Force push (only if repository is new and empty)
git push -u origin main --force --tags
```

---

## 📁 Repository Structure

```
medialog/
├── 📄 README.md              Documentation
├── 📄 LICENSE                MIT License
├── 📄 .gitignore             Excludes sensitive files
├── 📄 config.example.php     Config template
├── 📄 GITHUB_SETUP.md        Detailed guide
├── 🐘 *.php                  10 pages
├── 📁 assets/                Shared CSS
├── 📁 includes/              Components
└── 📁 scripts/               Import tools
```

---

## 🌐 Links After Setup

- **GitHub:** `https://github.com/USERNAME/medialog`
- **Live App:** `http://1n2.org/medialog/`
- **Case Study:** `http://1n2.org/case-studies/medialog/`
- **1n2.org:** `http://1n2.org`

---

## 💡 Pro Tips

1. **Star your own repo** - Shows it's actively maintained
2. **Add screenshot** - Put in `docs/images/` folder
3. **Pin repository** - Makes it appear on your profile
4. **Add GitHub Actions** - For automated testing
5. **Create Wiki** - For extended documentation

---

**Ready? Let's push! 🚀**

```bash
git remote add origin https://github.com/YOUR_USERNAME/medialog.git
git push -u origin main --tags
```
