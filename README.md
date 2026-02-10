# MediaLog

A modern media tracking application that combines Letterboxd (movies) and Goodreads (books) into a single, beautiful dashboard.

![MediaLog](https://img.shields.io/badge/version-5.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.x-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![License](https://img.shields.io/badge/license-MIT-green)

## 🎯 Overview

MediaLog is a personal media consumption tracker built entirely through human-AI collaboration. Track your books from Goodreads and movies from Letterboxd in one unified dashboard with advanced analytics, insights, and beautiful visualizations.

**Live Demo:** [medialog.1n2.org](http://1n2.org/medialog/)  
**Case Study:** [Full Development History](http://1n2.org/case-studies/medialog/)

## ✨ Features

### 📚 Books
- Import from Goodreads
- Track reading history
- Author analytics
- Page count tracking
- Reading pace analysis
- Year-end projections

### 🎬 Movies
- Import from Letterboxd
- Track viewing history
- Director analytics
- Genre analysis
- Decade trends
- Runtime statistics

### 📊 Analytics
- "On This Day" memories
- Reading/viewing streaks
- Pace tracking (books/day, movies/day)
- Monthly patterns
- Year-over-year comparisons
- Smart projections

### 🎨 Design
- Modern purple gradient aesthetic
- 3-column responsive homepage
- Visual poster galleries
- Mobile-first design
- Glass morphism effects
- Smooth animations

## 📸 Screenshots

![Homepage](docs/images/homepage.png)
*Modern 3-column dashboard with hero stats*

![Analytics](docs/images/analytics.png)
*Advanced insights with pace tracking and projections*

![Directors](docs/images/directors.png)
*Director analytics with poster grids*

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Web server (Apache/Nginx)

### Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/medialog.git
cd medialog

# Configure database
cp config.example.php config.php
nano config.php  # Edit database credentials

# Import database schema
mysql -u root -p < database/schema.sql

# Import your data (optional)
php scripts/import-goodreads.php
php scripts/import-letterboxd.php

# Start development server
php -S localhost:8000
```

Visit `http://localhost:8000` in your browser!

## 📁 Project Structure

```
medialog/
├── index.php              # Homepage with 3-column layout
├── books.php              # Books listing
├── movies.php             # Movies listing
├── authors.php            # Author analytics
├── directors.php          # Director analytics
├── stats.php              # Statistics page
├── insights.php           # Book insights
├── movie-insights.php     # Movie insights
├── review.php             # Individual book view
├── movie.php              # Individual movie view
├── config.php             # Database configuration
├── assets/
│   └── medialog.css       # Shared styles
├── includes/
│   └── footer.php         # Reusable footer
├── scripts/
│   ├── import-goodreads.php
│   ├── import-letterboxd.php
│   └── scraper-final.php  # Metadata scraper
└── database/
    └── schema.sql         # Database structure
```

## 🗄️ Database Schema

```sql
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT,              -- 6=Letterboxd, 7=Goodreads
    title VARCHAR(500),
    url TEXT,
    image_url TEXT,
    description TEXT,
    full_content TEXT,
    publish_date DATETIME,
    author VARCHAR(255),      -- For books
    director VARCHAR(255),    -- For movies
    genres VARCHAR(500),      -- Comma-separated
    runtime_minutes INT,      -- For movies
    INDEX(site_id),
    INDEX(publish_date)
);
```

## 📊 Data Sources

### Letterboxd (Movies)
- Film ratings and reviews
- Watch dates
- Director information
- Genres and runtime
- Poster images

### Goodreads (Books)
- Book ratings and reviews
- Read dates
- Author information
- Page counts
- Cover images

## 🛠️ Development

### Built With
- **Backend:** Pure PHP 8.x (no framework)
- **Database:** MySQL 8.0 with PDO
- **Frontend:** Pure CSS (no framework)
- **Design:** Custom purple gradient theme
- **Icons:** Unicode emojis
- **Images:** CDN from Letterboxd/Goodreads

### Key Features Implementation

#### "On This Day" Feature
```php
// Smart 365-day fallback for memories
if (empty($onThisDay)) {
    for ($daysBack = 1; $daysBack <= 365; $daysBack++) {
        $checkDate = date('m-d', strtotime("-{$daysBack} days"));
        $fallback = queryItemsByDate($checkDate);
        if (!empty($fallback)) {
            $onThisDay = $fallback;
            break;
        }
    }
}
```

#### Year-End Projections
```php
$daysInYear = 365;
$daysPassed = date('z') + 1;
$booksPerDay = $booksThisYear / $daysPassed;
$projected = $booksThisYear + ($booksPerDay * ($daysInYear - $daysPassed));
```

## 📈 Version History

### v5.0 - MediaLog Rebrand (Feb 9, 2026)
- 🏷️ Complete rebrand to "MediaLog"
- 📅 Smart year fallback logic
- 🎨 Shared design system
- 📝 Professional documentation

### v4.0 - Directors & Metadata (Feb 9, 2026)
- 🎬 Directors analytics page
- 🕷️ Letterboxd metadata scraper
- 👥 Multi-director film support
- 📊 Enhanced movie data

### v3.0 - Modern Homepage (Feb 9, 2026)
- 🎨 3-column responsive layout
- 📅 "On This Day" feature
- 🖼️ Visual poster gallery
- 📱 Mobile optimization

### v2.0 - Advanced Analytics (Feb 9, 2026)
- 📊 Statistics page
- 📚 Book insights with projections
- 🎬 Movie analytics
- 🔮 Streak detection

### v1.0 - Foundation (Feb 9, 2026)
- 🗄️ Database architecture
- 📥 Data import from sources
- 🎨 Basic UI
- 📄 Core pages

## 🤝 Contributing

This is a personal project, but suggestions and feedback are welcome!

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Letterboxd** - Movie data and poster images
- **Goodreads** - Book data and cover images
- **Claude (Anthropic)** - AI assistant for development
- **1n2.org** - Human-AI collaboration project

## 📚 Case Study

Want to see how this was built? Check out the complete [development case study](http://1n2.org/case-studies/medialog/) with version-by-version breakdown, technical decisions, and lessons learned.

**Development Stats:**
- ⏱️ **7.5 hours** total development time
- 📄 **10 pages** created
- ✨ **40+ features** implemented
- 💻 **~3,500 lines** of code
- 🤝 **100% AI-assisted** development

## 🔗 Links

- **Live Application:** [medialog.1n2.org](http://1n2.org/medialog/)
- **Case Study:** [Development History](http://1n2.org/case-studies/medialog/)
- **Version Tree:** [Visual Timeline](http://1n2.org/case-studies/medialog/version-tree.html)
- **1n2.org:** [Human + AI Collaboration](http://1n2.org)

## 📧 Contact

Thomas Hunt - [thomashunt.com](http://www.thomashunt.com)

Project Link: [https://github.com/yourusername/medialog](https://github.com/yourusername/medialog)

---

**Built with ❤️ through Human-AI Collaboration**  
*A 1n2.org project - One human, one AI, infinite possibilities*
