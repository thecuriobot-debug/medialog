# 📽️ Import ALL 1,565 Letterboxd Movies

## You have 1,565 films! 

Currently in database: **50 films**
Remaining to import: **~1,515 films**

---

## 🎯 BEST METHOD: CSV Export (Recommended)

Letterboxd provides a complete data export that includes everything:

### Step 1: Request Export
1. Go to: https://letterboxd.com/settings/data/
2. Click "EXPORT YOUR DATA"
3. Wait for email (usually arrives within minutes)

### Step 2: Download & Extract
4. Download the ZIP file from the email
5. Extract these files:
   - `diary.csv` - All films with watch dates
   - `ratings.csv` - All your ratings
   - `reviews.csv` - All your reviews
   - `watched.csv` - Complete watch history

### Step 3: Import to Hunt HQ
```bash
cd /Users/curiobot/Sites/1n2.org/hunt-hq
php import-letterboxd-csv.php ~/Downloads/letterboxd-export/diary.csv
```

This will import ALL 1,565 films with:
✅ Complete watch dates
✅ All ratings (stars)
✅ Full review text
✅ Rewatch indicators
✅ Film metadata

---

## ⚙️ ALTERNATIVE: Automated Scraping

I can create a script that scrapes all 1,565 films from your profile, but:
- Takes ~40 minutes (1.5 seconds per film × 1,565)
- May miss some data
- Puts load on Letterboxd servers
- Risk of being rate-limited

**CSV export is much faster and more reliable!**

---

## 📊 What You'll Get

After import, your Hunt HQ will have:
- **1,565 movies** (vs current 50)
- Complete rating history
- All reviews and watch dates
- Full statistics and analytics
- Movie posters for all films
- Proper sorting and filtering

---

## 🚀 Quick Start

**Option 1 (Recommended):**
Request CSV export now, then run import script when you get it.

**Option 2 (Automated):**
I can build the scraper if you prefer, but it will take time.

Which method would you prefer?
