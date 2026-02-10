# Hunt HQ - Movie Statistics Expansion

## ✅ What's New

### 1. Enhanced Statistics Page (stats.php)
**New Movie Stats:**
- 5 stat cards: Total Movies, This Year, Avg Rating, Per Month, With Reviews
- Rating distribution with percentages
- Movies watched by year chart
- Movies by decade (release year) - 1920s, 2020s, etc.
- Top release years chart

### 2. Advanced Insights Page (insights.php)
**Comprehensive Analytics:**
- **Current Pace:** Books/day, Movies/day, Pages/day
- **Streaks:** Current streak, longest streak
- **Projections:** Year-end projections for books, movies, pages
- **Monthly Breakdown:** Side-by-side books vs movies chart
- **This Month Performance:** Detailed current month stats
- **Peak Insights:** Automatically identifies peak months

**Features:**
- Dual-bar charts showing books vs movies by month
- Live pace calculations
- Projection based on current velocity
- Streak tracking (consecutive days with activity)
- All-time comparison stats

### 3. Movie Insights Page (movie-insights.php)
**Dedicated Movie Analysis:**
- Rating analysis with percentages
- Decade analysis (which decades you watch most)
- Viewing patterns by year
- Monthly viewing for current year
- Top release years

## 📁 Files Changed/Created

```
✅ stats.php - Enhanced with movie charts
✅ insights.php - NEW advanced analytics
✅ movie-insights.php - NEW movie-specific insights
```

## 🚀 Deployment Instructions

### Option 1: Manual Upload (Easiest)

```bash
# From your Mac terminal:
cd /Users/curiobot/Sites/1n2.org/hunt-hq

# Upload all 3 files:
scp stats.php insights.php movie-insights.php root@157.245.186.58:/var/www/html/hunt-hq/
```

### Option 2: Archive Upload

```bash
# Create archive:
tar -czf hunt-hq-insights.tar.gz stats.php insights.php movie-insights.php

# Upload:
scp hunt-hq-insights.tar.gz root@157.245.186.58:/tmp/

# Then SSH and extract:
ssh root@157.245.186.58
cd /var/www/html/hunt-hq
tar -xzf /tmp/hunt-hq-insights.tar.gz
chown www-data:www-data stats.php insights.php movie-insights.php
chmod 644 stats.php insights.php movie-insights.php
exit
```

## 🎯 What Each Page Shows

### Statistics (stats.php)
- Overview of books AND movies
- Rating distributions for both
- Yearly trends
- Author statistics
- Decade analysis

### Insights (insights.php)  
- Real-time pace (books/day, movies/day)
- Streak tracking
- Year-end projections
- Monthly comparison charts
- This month deep dive

### Movie Insights (movie-insights.php)
- Pure movie analytics
- Rating breakdowns
- Decade preferences
- Release year patterns
- Monthly viewing habits

## 📊 New Statistics Calculated

**Pace Metrics:**
- Books per day (current year)
- Movies per day (current year)
- Pages per day (current year)

**Projections:**
- Projected books by year end
- Projected movies by year end
- Projected pages by year end

**Streaks:**
- Current consecutive days
- Longest streak ever

**Comparisons:**
- Books vs Movies by month
- Peak months for each
- All-time ratios

## 🌐 Live URLs (After Deployment)

- http://1n2.org/hunt-hq/stats.php
- http://1n2.org/hunt-hq/insights.php
- http://1n2.org/hunt-hq/movie-insights.php

## 🧪 Local Testing

```bash
# Open in browser:
http://localhost:8000/hunt-hq/stats.php
http://localhost:8000/hunt-hq/insights.php
http://localhost:8000/hunt-hq/movie-insights.php
```

## 🎨 Visual Features

- Dual-color bar charts (gold for books, gray for movies)
- Highlighted "current pace" cards with gradient backgrounds
- Interactive hover states
- Responsive design for mobile
- Percentage breakdowns
- Comparative visualizations

## 📈 Future Enhancement Ideas

- Add director tracking
- Genre analysis
- Runtime statistics
- Rewatch tracking
- Seasonal patterns
- Year-over-year comparisons
- Achievement badges
- Social comparisons

## ✨ Key Improvements Over Previous Version

1. **More granular movie data** - Decade, release year, watch patterns
2. **Comparative analytics** - Books vs Movies side-by-side
3. **Predictive insights** - Year-end projections
4. **Streak tracking** - Motivation through consistency
5. **Monthly deep dives** - Current month performance
6. **Visual clarity** - Better charts with dual-bar comparisons
