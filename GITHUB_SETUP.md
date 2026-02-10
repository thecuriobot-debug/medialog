# 🚀 MediaLog GitHub Setup Guide

## Step-by-Step Instructions

### 1. Create Repository on GitHub

1. Go to https://github.com/new
2. Fill in:
   - **Repository name:** `medialog`
   - **Description:** `Modern media tracking dashboard combining Letterboxd + Goodreads | Built with PHP & MySQL through human-AI collaboration`
   - **Visibility:** Public (or Private if preferred)
   - **⚠️ DO NOT** initialize with README, .gitignore, or license (we already have these)
3. Click "Create repository"

### 2. Set Up Local Git Repository

Run the setup script:

```bash
cd /Users/curiobot/Sites/1n2.org/medialog
chmod +x setup-git-repo.sh
./setup-git-repo.sh
```

This will:
- Initialize Git repository
- Create 5 commits (one for each version)
- Tag each version (v1.0 through v5.0)
- Set up complete version history

### 3. Connect to GitHub

After creating the repository on GitHub, copy the commands they show you, or use these:

```bash
cd /Users/curiobot/Sites/1n2.org/medialog

# Add GitHub remote (replace USERNAME with your GitHub username)
git remote add origin https://github.com/USERNAME/medialog.git

# Verify remote
git remote -v

# Push everything including tags
git push -u origin main --tags
```

### 4. Verify Upload

Visit your repository on GitHub. You should see:
- ✅ All files uploaded
- ✅ README.md displayed
- ✅ 5 tags in Releases
- ✅ Complete commit history

## 📊 What Gets Pushed to GitHub

### Files Included:
```
medialog/
├── README.md              ✅ Project documentation
├── LICENSE                ✅ MIT License
├── .gitignore             ✅ Ignore config & sensitive files
├── config.example.php     ✅ Configuration template
├── *.php                  ✅ All 10 pages
├── assets/                ✅ Shared CSS
├── includes/              ✅ Reusable components
├── scripts/               ✅ Import & scraper scripts
└── docs/                  ✅ Documentation
```

### Files Excluded (.gitignore):
- ❌ `config.php` (contains database credentials)
- ❌ `.DS_Store` (macOS files)
- ❌ `*.log` (log files)
- ❌ Database dumps

### Version Tags:
- 🏷️ `v1.0` - Foundation & Data Integration
- 🏷️ `v2.0` - Advanced Analytics
- 🏷️ `v3.0` - Modern Homepage
- 🏷️ `v4.0` - Directors & Metadata
- 🏷️ `v5.0` - MediaLog Rebrand (current)

## 🎯 After Pushing

### Create Releases

1. Go to your repository on GitHub
2. Click "Releases" → "Create a new release"
3. For each tag (v1.0, v2.0, etc.):
   - Select the tag
   - Add release title: "Version X.X - Name"
   - Copy description from tag message
   - Publish release

### Add Topics

Add topics to your repository for discoverability:
```
php
mysql
letterboxd
goodreads
media-tracker
reading-tracker
movie-tracker
analytics
dashboard
ai-assisted
```

### Update Repository Settings

1. **About** (right sidebar):
   - Website: `http://1n2.org/medialog/`
   - Topics: Add the topics above
   - ✅ Include in search

2. **Description:**
   ```
   Modern media tracking dashboard combining Letterboxd + Goodreads | 
   Built with PHP & MySQL through human-AI collaboration | 
   7.5 hours from concept to production
   ```

## 📝 Update README with Your GitHub Username

After pushing, update the README.md:

```bash
cd /Users/curiobot/Sites/1n2.org/medialog

# Replace "yourusername" with your actual GitHub username
sed -i '' 's/yourusername/YOUR_ACTUAL_USERNAME/g' README.md

# Commit and push the change
git add README.md
git commit -m "docs: update GitHub username in README"
git push
```

## 🌐 Optional: GitHub Pages

If you want to host the case study on GitHub Pages:

```bash
# Create gh-pages branch
git checkout -b gh-pages

# Copy case study
cp -r /Users/curiobot/Sites/1n2.org/case-studies/medialog/* .

# Commit and push
git add .
git commit -m "docs: add case study to GitHub Pages"
git push -u origin gh-pages

# Enable in Settings → Pages → Source: gh-pages branch
```

Your case study will be available at:
`https://USERNAME.github.io/medialog/`

## 🔄 Future Updates

When you make changes to MediaLog:

```bash
# Make changes to files
# ...

# Stage changes
git add .

# Commit with descriptive message
git commit -m "feat: add new feature"

# Push to GitHub
git push

# Create new version tag when ready
git tag -a v5.1 -m "Version 5.1 - Description"
git push --tags
```

## 📊 Viewing Version History

On GitHub, users can:
- View commits: Click "Commits" to see all changes
- Browse tags: Click "Tags" to see all versions
- Compare versions: Use compare feature
- Clone specific version: `git clone --branch v1.0 URL`

## 🎉 You're Done!

Your MediaLog repository is now on GitHub with:
- ✅ Complete version history
- ✅ Tagged releases (v1.0 - v5.0)
- ✅ Professional README
- ✅ MIT License
- ✅ Clean .gitignore
- ✅ Example configuration

Share your work:
- Repository: `https://github.com/USERNAME/medialog`
- Live demo: `http://1n2.org/medialog/`
- Case study: `http://1n2.org/case-studies/medialog/`

---

**Questions?**
- GitHub username format: all lowercase, no spaces
- Default branch: GitHub uses `main` (not `master`)
- Tags are automatically created as Releases on GitHub
