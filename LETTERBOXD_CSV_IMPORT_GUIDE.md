# 📥 Letterboxd Enhanced Import Guide

## Why Export from Letterboxd?

**Current limitation:** RSS feed only shows ~50 most recent films

**CSV Export gives you:**
- ✅ **Complete history** - Every film you've ever logged
- ✅ **Exact watch dates** - Know when you watched each film
- ✅ **Reviews** - Import your full review text
- ✅ **Ratings** - All your star ratings
- ✅ **Rewatches** - Track multiple viewings
- ✅ **Tags** - Your custom tags

---

## Step-by-Step Export & Import

### 1️⃣ **Export Your Data from Letterboxd**

1. **Log into Letterboxd**
   - Go to: https://letterboxd.com

2. **Go to Data Settings**
   - Click your profile → **Settings**
   - Click **"Import & Export"** or **"Data"** in left menu
   - Or direct link: https://letterboxd.com/settings/data/

3. **Request Export**
   - Click **"Export Your Data"** button
   - Letterboxd will process your request
   - You'll receive an email (usually within 5-10 minutes)

4. **Download ZIP File**
   - Open the email from Letterboxd
   - Click the download link
   - Save the ZIP file to your computer

5. **Extract the ZIP**
   - Unzip the file
   - You'll see multiple CSV files:
     - `diary.csv` ⭐ **BEST - has dates!**
     - `watched.csv` - All watched films
     - `ratings.csv` - Your ratings
     - `reviews.csv` - Your reviews
     - `watchlist.csv` - Your watchlist

---

### 2️⃣ **Prepare the CSV for Import**

**Recommended:** Use `diary.csv` (has watch dates!)

1. **Rename the file**
   ```
   From: diary.csv
   To:   letterboxd-data.csv
   ```

2. **Copy to MediaLog directory**
   ```bash
   cp ~/Downloads/letterboxd-data.csv /Users/curiobot/Sites/1n2.org/medialog/
   ```

---

### 3️⃣ **Import to MediaLog**

**Option A: Local Development**

```bash
cd /Users/curiobot/Sites/1n2.org/medialog
php import-letterboxd-csv.php
```

**Option B: Production Server**

```bash
# Upload CSV to server
scp letterboxd-data.csv root@157.245.186.58:/var/www/html/medialog/

# Upload import script
scp import-letterboxd-csv.php root@157.245.186.58:/var/www/html/medialog/

# SSH into server
ssh root@157.245.186.58

# Run import
cd /var/www/html/medialog
php import-letterboxd-csv.php
```

---

## 📋 CSV Format Reference

### diary.csv (Recommended)
```csv
Date,Name,Year,Letterboxd URI,Rating,Rewatch,Tags,Watched Date
2024-12-25,The Matrix,1999,/film/the-matrix/,4.5,No,"sci-fi, action",2024-12-25
```

**Columns:**
- `Date` - Watch date (YYYY-MM-DD) ⭐
- `Name` - Film title
- `Year` - Release year
- `Letterboxd URI` - Film page path
- `Rating` - Your rating (0-5)
- `Rewatch` - Yes/No
- `Tags` - Your tags (comma-separated)

### watched.csv
```csv
Name,Year,Letterboxd URI,Rating
The Matrix,1999,/film/the-matrix/,4.5
```

**Note:** No dates! Will use import date.

---

## 🔍 What Gets Imported

### Into MediaLog Database

**Table:** `posts`

**Fields Updated:**
- `title` - "Film Name, Year - ★★★★"
- `url` - https://letterboxd.com/film/...
- `description` - "Watched on YYYY-MM-DD - Rating: 4.5/5"
- `full_content` - Your review text
- `publish_date` - Actual watch date!
- `site_id` - 6 (Letterboxd)

### Example Result

**Before (RSS):**
```
Title: The Matrix, 1999 - ★★★★
Date: 2024-12-25 (import date)
Review: (empty)
```

**After (CSV):**
```
Title: The Matrix, 1999 - ★★★★
Date: 2024-12-25 (actual watch date!)
Review: "Mind-bending sci-fi masterpiece..."
Tags: sci-fi, action
Rewatch: No
```

---

## 🎯 Import Script Features

### Smart Handling
- ✅ **Deduplication** - Won't create duplicates
- ✅ **Updates** - Updates existing entries with new data
- ✅ **Star Conversion** - Converts 4.5 rating → ★★★★
- ✅ **Date Parsing** - Handles multiple date formats
- ✅ **Review Import** - Preserves full review text
- ✅ **Tag Import** - Imports your tags

### Error Handling
- ❌ Checks if CSV file exists
- ❌ Validates CSV format
- ❌ Reports errors clearly
- ❌ Shows import summary

### Progress Display
```
✅ Imported: The Matrix (1999) - 2024-12-25
✅ Imported: Inception (2010) - 2024-12-24
📝 Updated: Interstellar (2014) - 2024-12-20
...

========================
📊 Import Summary:
========================
✅ Imported: 45 new movies
📝 Updated:  5 existing movies
⏭️  Skipped:  0 duplicates

🎬 Total processed: 50

✨ Import complete!
```

---

## 💡 Pro Tips

### 1. **Use diary.csv for Best Results**
- Has exact watch dates
- Includes rewatches
- Most complete data

### 2. **Export Regularly**
- Export monthly to keep MediaLog synced
- Letterboxd allows unlimited exports

### 3. **Combine with RSS**
- Use CSV for historical import (one-time)
- Use RSS for ongoing updates (automated)

### 4. **Backup First**
```bash
# Backup database before import
mysqldump -u root myapp_db > backup-before-letterboxd-import.sql
```

### 5. **Run Metadata Scraper After**
```bash
# Get director, genre, runtime data
php scraper-final.php
```

---

## 🚀 Quick Start (TL;DR)

```bash
# 1. Export from Letterboxd
#    Visit: https://letterboxd.com/settings/data/
#    Click "Export Your Data"
#    Download ZIP from email

# 2. Extract and rename
unzip letterboxd-export.zip
mv diary.csv letterboxd-data.csv

# 3. Copy to MediaLog
cp letterboxd-data.csv /Users/curiobot/Sites/1n2.org/medialog/

# 4. Import
cd /Users/curiobot/Sites/1n2.org/medialog
php import-letterboxd-csv.php

# 5. Get metadata
php scraper-final.php

# 6. Done! 🎉
```

---

## 📊 Expected Results

### Before CSV Import
- 50 movies (from RSS)
- Generic dates
- No reviews
- No tags

### After CSV Import
- **500+ movies** (your full history!)
- **Exact watch dates**
- **Full reviews**
- **All tags**
- **Rewatch tracking**

---

## 🆘 Troubleshooting

### "CSV file not found"
```bash
# Check file location
ls -la /Users/curiobot/Sites/1n2.org/medialog/letterboxd-data.csv

# Move if in wrong location
mv ~/Downloads/diary.csv /Users/curiobot/Sites/1n2.org/medialog/letterboxd-data.csv
```

### "Unrecognized CSV format"
- Make sure you're using `diary.csv` or `watched.csv`
- Don't use `watchlist.csv` or `lists.csv`
- Check file isn't corrupted

### "Permission denied"
```bash
chmod +x import-letterboxd-csv.php
chmod 644 letterboxd-data.csv
```

### "Database connection failed"
- Check config.php has correct credentials
- Verify MySQL is running
- Test connection: `php -r "require 'config.php'; getDB();"`

---

## 🎬 Next Steps After Import

1. **Run Metadata Scraper**
   ```bash
   php scraper-final.php
   ```
   Gets: Directors, genres, runtime

2. **Deploy to Production**
   ```bash
   # Database is already updated, just refresh pages
   ```

3. **Verify Data**
   - Check: http://1n2.org/medialog/movies.php
   - Verify: Watch dates are accurate
   - Confirm: Reviews are imported

4. **Enjoy Enhanced Analytics!**
   - More accurate statistics
   - Better timeline
   - Complete viewing history

---

**Ready to import your complete Letterboxd history?** 🚀

Let me know when you've downloaded the CSV and I'll help with the import!
