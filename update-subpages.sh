#!/bin/bash
# MediaLog - Update all subpages to match modern dashboard design

echo "🎨 Updating MediaLog subpages to match dashboard design..."
echo ""

cd /Users/curiobot/Sites/1n2.org/medialog

# Create list of pages to update
PAGES=(
    "books.php"
    "movies.php"
    "authors.php"
    "directors.php"
    "stats.php"
    "insights.php"
    "movie-insights.php"
    "review.php"
    "movie.php"
)

echo "📄 Pages to update: ${#PAGES[@]}"
for page in "${PAGES[@]}"; do
    echo "  - $page"
done

echo ""
echo "✅ Ready to apply modern dashboard styling to all pages!"
echo ""
echo "Design elements to apply:"
echo "  • Modern navigation with glass morphism"
echo "  • Purple gradient background"
echo "  • Gold accent branding (#d4af37)"
echo "  • Consistent card styling"
echo "  • Responsive 3-column layouts where appropriate"
echo "  • Smooth animations"
echo "  • Mobile-first responsive design"
echo ""
