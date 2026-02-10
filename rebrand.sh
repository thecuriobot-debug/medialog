#!/bin/bash

# Script to rebrand all Hunt HQ pages to clean Media branding

echo "🎨 Rebranding Hunt HQ to Media Tracker..."

cd /Users/curiobot/Sites/1n2.org/hunt-hq

# Function to update a file
update_file() {
    file=$1
    echo "  Updating $file..."
    
    # Replace Hunt HQ with MEDIA in nav
    sed -i '' 's/HUNT HQ/MEDIA/g' "$file"
    
    # Replace page titles
    sed -i '' 's/Hunt HQ -/Media Tracker -/g' "$file"
    sed -i '' 's/Hunt HQ/Media Tracker/g' "$file"
    
    # Update taglines
    sed -i '' 's/personal media consumption dashboard/Letterboxd + Goodreads tracker/g' "$file"
    sed -i '' 's/Media consumption dashboard/Letterboxd + Goodreads tracker/g' "$file"
}

# Update all PHP pages
for file in stats.php insights.php movie-insights.php books.php movies.php authors.php review.php movie.php; do
    if [ -f "$file" ]; then
        update_file "$file"
    fi
done

echo "✅ Rebranding complete!"
echo ""
echo "Updated files:"
echo "  - stats.php"
echo "  - insights.php"
echo "  - movie-insights.php"
echo "  - books.php"
echo "  - movies.php"
echo "  - authors.php"
echo "  - review.php"
echo "  - movie.php"
echo ""
echo "Branding changes:"
echo "  ✅ Navigation: HUNT HQ → MEDIA"
echo "  ✅ Titles: Hunt HQ → Media Tracker"
echo "  ✅ Taglines: Updated to mention Letterboxd + Goodreads"
