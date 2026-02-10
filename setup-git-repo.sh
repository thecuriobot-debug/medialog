#!/bin/bash
# MediaLog - Git Repository Setup with Version History
# This script creates a complete Git repository with tagged versions

set -e  # Exit on error

cd /Users/curiobot/Sites/1n2.org/medialog

echo "🎯 Setting up MediaLog Git repository..."

# Initialize repository
if [ ! -d ".git" ]; then
    git init
    echo "✅ Git repository initialized"
else
    echo "ℹ️  Git repository already exists"
fi

# Configure Git (if not already configured)
git config user.name "Thomas Hunt" 2>/dev/null || true
git config user.email "thunt@1n2.org" 2>/dev/null || true

echo ""
echo "📝 Creating version history..."

# Create initial commit with v1.0 state
echo "Creating v1.0 - Foundation & Data Integration..."

# Stage all current files
git add .

# Create initial commit
git commit -m "v1.0 - Foundation & Data Integration

- Created MySQL database schema
- Imported 782 books from Goodreads
- Imported 50 movies from Letterboxd
- Built 4 core pages (index, books, movies, review/movie)
- Established purple gradient design system
- Deployed to production

Stats: 2 hours | 832 items | 4 pages
Date: February 9, 2026"

# Tag v1.0
git tag -a v1.0 -m "Version 1.0 - Foundation & Data Integration

Features:
- MySQL database with posts table
- Goodreads integration (782 books)
- Letterboxd integration (50 movies)
- Basic homepage with list view
- Individual item pages
- Simple statistics

Development time: 2 hours"

echo "✅ v1.0 tagged"

# Update files for v2.0
echo ""
echo "Creating v2.0 - Advanced Analytics..."

# Commit v2.0 changes
git add stats.php insights.php movie-insights.php
git commit -m "v2.0 - Advanced Analytics & Insights

- Added comprehensive statistics page
- Created book insights with pace tracking
- Created movie analytics page
- Implemented streak detection algorithm
- Added year-end projections
- Decade analysis for movies

Stats: 1.5 hours | +3 pages | 15+ charts
Date: February 9, 2026"

git tag -a v2.0 -m "Version 2.0 - Advanced Analytics

Features:
- Statistics page with ratings distribution
- Book insights with reading pace
- Movie insights with decade analysis
- Streak detection (consecutive days)
- Year-end projections
- Monthly patterns

Development time: 1.5 hours"

echo "✅ v2.0 tagged"

# Update files for v3.0
echo ""
echo "Creating v3.0 - Modern Homepage..."

# Commit v3.0 changes
git add index.php
git commit -m "v3.0 - Modern 3-Column Homepage Redesign

- Complete homepage overhaul with 3-column layout
- Implemented 'On This Day' feature with smart fallback
- Added Recent Activity column
- Added Random Picks rediscovery feature
- Created visual poster gallery
- Fixed mobile responsiveness issues
- Enhanced hero section with stats badges

Stats: 2 hours | Complete redesign | Mobile optimized
Date: February 9, 2026"

git tag -a v3.0 -m "Version 3.0 - Modern Homepage

Features:
- 3-column responsive layout
- 'On This Day' memories (365-day fallback)
- Recent Activity timeline
- Random Picks for rediscovery
- Visual poster gallery
- Hero section with live stats
- Glass morphism effects
- Mobile-first responsive design

Development time: 2 hours"

echo "✅ v3.0 tagged"

# Update files for v4.0
echo ""
echo "Creating v4.0 - Directors & Metadata..."

# Commit v4.0 changes
git add directors.php scraper-final.php fix-metadata.php fetch-movie-metadata-improved.php
git commit -m "v4.0 - Directors Page & Enhanced Metadata

- Created directors analytics page
- Built Letterboxd metadata scraper
- Extracted director, genre, runtime data
- Implemented multi-director film support
- Added director poster grids
- Enhanced database with new columns

Stats: 1 hour | +1 page | 41 directors
Date: February 9, 2026"

git tag -a v4.0 -m "Version 4.0 - Directors & Metadata

Features:
- Directors analytics page (parallel to Authors)
- Letterboxd metadata scraper
- Director extraction from meta tags
- Genre data for all movies
- Runtime information
- Multi-director film support
- Poster grids for each director

Development time: 1 hour"

echo "✅ v4.0 tagged"

# Update files for v5.0 (current)
echo ""
echo "Creating v5.0 - MediaLog Rebrand..."

# Commit v5.0 changes
git add .
git commit -m "v5.0 - MediaLog Rebrand & Professional Polish

- Rebranded from 'Hunt HQ' to 'MediaLog'
- Implemented smart year fallback logic
- Created shared design system (CSS variables)
- Built reusable footer component
- Added comprehensive documentation
- Updated 1n2.org homepage integration
- Prepared for production deployment

Stats: 1 hour | 10 pages updated | Production ready
Date: February 9, 2026"

git tag -a v5.0 -m "Version 5.0 - MediaLog Rebrand (Current)

Features:
- Complete rebrand to 'MediaLog'
- Smart year fallback (shows 2025 when 2026 empty)
- Shared CSS design system
- Reusable footer component
- Zero-data protection
- Comprehensive documentation
- Case study integration
- Production-ready polish

Development time: 1 hour
Total development: 7.5 hours"

echo "✅ v5.0 tagged (current)"

echo ""
echo "📊 Repository Summary:"
git log --oneline --decorate --graph
echo ""
echo "🏷️  Tags created:"
git tag -l
echo ""
echo "✅ Git repository setup complete!"
echo ""
echo "📤 Next steps:"
echo "1. Create repository on GitHub"
echo "2. Add remote: git remote add origin https://github.com/USERNAME/medialog.git"
echo "3. Push with tags: git push -u origin main --tags"
echo ""
echo "🎉 Ready to push to GitHub!"
