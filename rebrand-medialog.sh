#!/bin/bash

echo "🎨 Rebranding to MediaLog and adding Directors..."

cd /Users/curiobot/Sites/1n2.org/hunt-hq

# Function to update navigation
update_nav() {
    file=$1
    echo "  Updating $file..."
    
    # Replace MEDIA with MEDIALOG
    sed -i '' 's/>MEDIA</>MEDIALOG</g' "$file"
    sed -i '' 's/Media Tracker/MediaLog/g' "$file"
    
    # Add Directors link after Authors (if not already present)
    if ! grep -q "directors.php" "$file"; then
        sed -i '' 's/<li><a href="authors.php">Authors<\/a><\/li>/<li><a href="authors.php">Authors<\/a><\/li>\
                <li><a href="directors.php">Directors<\/a><\/li>/g' "$file"
    fi
}

# Update all pages
for file in index.php stats.php insights.php movie-insights.php books.php movies.php authors.php review.php movie.php directors.php; do
    if [ -f "$file" ]; then
        update_nav "$file"
    fi
done

echo "✅ Rebranding complete!"
echo ""
echo "Changes:"
echo "  ✅ MEDIA → MEDIALOG"
echo "  ✅ Media Tracker → MediaLog"
echo "  ✅ Added Directors to navigation"
