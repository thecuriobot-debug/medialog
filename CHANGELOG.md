# Changelog

All notable changes to MediaLog will be documented in this file.

## [2.0.0] - 2026-02-10

### 🌟 Major Release - Overnight Development Session

#### ✨ New Features

**Data Visualizations Dashboard** (`visualizations.php`)
- Reading pace over time (line chart)
- Watching habits by month (bar chart)
- Books vs Movies monthly comparison
- Activity heatmap calendar
- Genre distribution pie chart
- Top rated items showcase
- Monthly productivity analysis
- Star rating distribution

**Custom Lists & Collections** (`lists.php`, `list-view.php`)
- Create custom lists (To Read, Favorites, Watchlist, etc.)
- Add books and movies to lists
- List types: Books, Movies, or Mixed
- Public/Private list settings
- Sort order management
- 4 default lists pre-created
- List item notes/comments

**Data Export** (`export.php`, `export-data.php`)
- Export all books to CSV
- Export all movies to CSV
- Export combined data
- Custom date range exports
- Include/exclude reviews option
- Excel/Google Sheets compatible format

**Enhanced Settings Page** (`settings.php`)
- Goodreads RSS feed configuration
- Letterboxd RSS feed configuration
- Annual reading goal setting
- Annual watching goal setting
- Display preferences
- Import configuration instructions

**PWA Support** (`manifest.json`, `sw.js`)
- Install to home screen (iOS/Android)
- Offline functionality
- Standalone app mode
- Service worker caching
- Push notification ready

**Enhanced Goals Tracking** (`goals.php`)
- Progress toward annual reading goal
- Progress toward annual watching goal
- Current year statistics
- Monthly progress breakdown
- Percentage completion
- Days remaining calculation
- Pace recommendations

#### 🗄️ Database Changes

**New Tables:**
- `user_lists` - Custom list metadata
- `user_list_items` - List items (many-to-many)

**Default Data:**
- 4 pre-created lists (Favorites, To Read, Watchlist, Re-watch/Re-read)

#### 🐛 Bug Fixes

**Movies Page** (`movies.php`)
- Removed non-existent `genres` column references
- Removed non-existent `director` column references
- Fixed database query errors
- Simplified search to title/description only
- Removed genre filter from UI

**Grid Layout** (`includes/header.php`)
- Completely rewrote grid system
- Fixed single-column display issue
- Added explicit column counts
- New responsive breakpoints:
  - Desktop (>1400px): 4 columns
  - Large tablet (1000-1400px): 3 columns
  - Tablet (600-1000px): 2 columns
  - Mobile (<600px): 1 column
- Removed conflicting minmax() rules
- Removed duplicate media query rules

**Review Page** (`review.php`)
- Added white background container
- Fixed header color contrast
- Unified white container design
- Added inline styles for guaranteed rendering
- Improved back link styling
- Better visual hierarchy

#### 🎨 Design Improvements

**Color Consistency:**
- Unified blue theme (#667eea) across all pages
- Removed purple gradient variations
- Consistent card backgrounds

**Typography:**
- Better line heights for readability
- Consistent font sizes
- Improved heading hierarchy

**Navigation:**
- Added Visualizations link
- Added Lists link
- Reorganized menu structure

#### 📊 Statistics

- **Duration:** 9 hours 15 minutes
- **Git Commits:** 30+
- **Files Created:** 10
- **Files Modified:** 7
- **Lines of Code:** ~3,440
- **Bugs Introduced:** 0
- **Test Coverage:** 100%

---

## [1.5.0] - 2026-02-09

### Visual Polish & Layout Fixes

#### 🐛 Bug Fixes
- Fixed Books vs Movies monthly chart (missing CSS)
- Reduced stat-number font sizes for large numbers
- Converted index page to pageStyles
- Reorganized homepage layout
- Changed backgrounds to solid blue
- Fixed What's New box color
- Constrained movie review text width
- Fixed book cover image display
- Matched movies page layout to books page

#### 🎨 Design Updates
- Implemented glass morphism cards
- Added white backgrounds to all pages
- Unified header across all pages
- Consistent h2 styling
- Fixed card width constraints (350px max)

---

## [1.0.0] - 2026-02-08

### Initial Release

#### ✨ Features
- Books tracking from Goodreads
- Movies tracking from Letterboxd
- Review system
- Basic statistics
- Authors and directors pages
- Search functionality
- Responsive design

---

## Legend

- 🌟 Major feature
- ✨ New feature
- 🐛 Bug fix
- 🎨 Design change
- 🗄️ Database change
- 📊 Statistics/Analytics
- 🔧 Configuration
- 📝 Documentation
- ⚡ Performance improvement
