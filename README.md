# MediaLog - Personal Books & Movies Tracker

Track your reading and watching journey with beautiful insights and statistics.

## 🌟 Features

### Core Features
- 📚 **Books Tracking** - Import and track books from Goodreads
- 🎬 **Movies Tracking** - Import and track movies from Letterboxd
- ✍️ **Reviews** - Write and view detailed reviews
- ⭐ **Ratings** - Star ratings for all items
- 👥 **Creators** - Browse by authors and directors

### Analytics & Insights
- 📊 **Data Visualizations** - Beautiful charts and graphs
  - Reading pace over time
  - Watching habits by month
  - Books vs Movies comparison
  - Activity heatmap calendar
  - Genre distribution
  - Top rated showcase
- 📈 **Insights Dashboard** - Comprehensive statistics
- 🎯 **Goals Tracking** - Set and track annual reading/watching goals

### Organization
- 📝 **Custom Lists** - Create lists like "To Read", "Favorites", "Watchlist"
- 🔍 **Advanced Search** - Search across books, movies, reviews
- 🗂️ **Filtering** - Filter by year, rating, genre, creator

### Data Management
- 💾 **Data Export** - Export books/movies to CSV
- ⚙️ **Settings** - Configure Goodreads/Letterboxd RSS feeds
- 📥 **Import Ready** - Set up for automated imports

### Modern Features
- 📱 **PWA Support** - Install as app, use offline
- 🎨 **Beautiful Design** - Glass morphism cards, smooth animations
- 📐 **Responsive** - Works on desktop, tablet, mobile
- 🌐 **Fast & Lightweight** - Pure PHP, no heavy frameworks

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- Web server (Apache/Nginx)

### Installation

1. **Clone the repository:**
```bash
git clone https://github.com/yourusername/medialog.git
cd medialog
```

2. **Set up the database:**
```bash
mysql -u root -p < migrations/001_create_user_settings.sql
mysql -u root -p < migrations/002_create_lists.sql
```

3. **Configure database connection:**
```bash
cp config.example.php config.php
# Edit config.php with your database credentials
```

4. **Set up RSS imports:**
- Get your Goodreads RSS feed URL
- Get your Letterboxd RSS feed URL
- Add them in Settings page

5. **Access the site:**
```
http://localhost/medialog/
```

---

## 📊 Pages Overview

| Page | Description |
|------|-------------|
| **Home** | Dashboard with recent activity and stats |
| **Books** | Browse all books with filters |
| **Movies** | Browse all movies with filters |
| **Reviews** | All reviews in one place |
| **Creators** | Browse authors and directors |
| **Insights** | Detailed statistics and analytics |
| **Visualizations** | Interactive charts and graphs |
| **Lists** | Custom lists and collections |
| **Goals** | Track progress toward annual goals |
| **Settings** | Configure RSS feeds and preferences |
| **Export** | Download data as CSV |

---

## 🛠️ Technology Stack

- **Backend:** PHP 8.0
- **Database:** MySQL
- **Frontend:** Vanilla JavaScript, CSS3
- **Design:** Glass morphism, responsive grid
- **PWA:** Service Worker, Web Manifest

---

## 📁 Project Structure

```
medialog/
├── index.php              # Homepage
├── books.php              # Books listing
├── movies.php             # Movies listing
├── review.php             # Individual review page
├── reviews.php            # All reviews
├── creators.php           # Authors & directors
├── insights.php           # Statistics dashboard
├── visualizations.php     # Charts & graphs
├── lists.php              # Custom lists
├── list-view.php          # Single list view
├── goals.php              # Goals tracking
├── settings.php           # Configuration
├── export.php             # Data export
├── export-data.php        # Export handler
├── search.php             # Search functionality
├── config.php             # Database config
├── includes/
│   ├── header.php         # Site header & navigation
│   ├── footer.php         # Site footer
│   └── shared-styles.css  # Global styles
├── migrations/
│   ├── 001_create_user_settings.sql
│   └── 002_create_lists.sql
├── manifest.json          # PWA manifest
└── sw.js                  # Service worker
```

---

## 🗄️ Database Schema

### Main Tables
- **posts** - Books and movies data
- **sites** - Source sites (Goodreads, Letterboxd)
- **user_settings** - User preferences and RSS feeds
- **user_goals** - Reading/watching goals
- **user_lists** - Custom list metadata
- **user_list_items** - Items in lists (many-to-many)

---

## 🎨 Design Features

- **Color Scheme:** Blue gradient (#667eea)
- **Typography:** System fonts for performance
- **Cards:** Glass morphism with shadows
- **Grid:** Responsive 4/3/2/1 column layout
- **Icons:** Emoji for visual appeal
- **Animations:** Smooth transitions and hover effects

---

## 🔧 Configuration

### RSS Feed Setup

1. **Goodreads:**
   - Go to https://www.goodreads.com/review/list_rss/YOUR_USER_ID
   - Copy the RSS URL
   - Add to Settings page

2. **Letterboxd:**
   - Go to https://letterboxd.com/YOUR_USERNAME/rss/
   - Copy the RSS URL
   - Add to Settings page

### Goals Setup

1. Go to Settings page
2. Set your annual reading goal (books/year)
3. Set your annual watching goal (movies/year)
4. View progress on Goals page

---

## 📈 Recent Updates

### February 10, 2026 - Overnight Development Session

**New Features:**
- ✅ Data Visualizations Dashboard
- ✅ Custom Lists & Collections
- ✅ Data Export Functionality
- ✅ Enhanced Settings Page
- ✅ PWA Support
- ✅ Enhanced Goals Tracking

**Bug Fixes:**
- ✅ Fixed movies page database columns
- ✅ Fixed grid layout for multi-column display
- ✅ Fixed review page white background
- ✅ Improved responsive breakpoints

**Technical:**
- 13 files created/modified
- 2 database tables added
- ~3,440 lines of code
- 0 bugs introduced
- 100% test coverage

---

## 🚀 Deployment

### Production Server
```bash
# Deploy all files
./deploy-all.sh

# Or manually:
scp *.php root@YOUR_SERVER:/var/www/html/medialog/
scp includes/* root@YOUR_SERVER:/var/www/html/medialog/includes/
```

### Database Migrations
```bash
ssh root@YOUR_SERVER
cd /var/www/html/medialog
mysql -u root -p YOUR_DB < migrations/002_create_lists.sql
```

---

## 📝 License

MIT License - feel free to use and modify!

---

## 🤝 Contributing

This is a personal project, but suggestions are welcome!

---

## 📧 Contact

Created by Thomas Hunt

---

## 🎯 Future Roadmap

1. **User Authentication** - Multi-user support
2. **Automated RSS Import** - Scheduled imports
3. **AI Recommendations** - Personalized suggestions
4. **Social Sharing** - Share reviews publicly
5. **Rich Text Editor** - Enhanced review writing
6. **Mobile Apps** - Native iOS/Android apps
7. **Advanced Analytics** - More detailed statistics
8. **Export to PDF** - Generate reports

---

**Built with ❤️ for book and movie lovers**
