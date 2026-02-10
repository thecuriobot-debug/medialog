#!/bin/bash
# Convert all main pages to use unified header

echo "🔄 Converting all pages to unified header..."

# List of pages to convert
PAGES=(
    "index.php"
    "stats.php"
    "insights.php"
    "authors.php"
    "directors.php"
    "review.php"
    "movie.php"
)

echo "📋 Pages to convert: ${PAGES[@]}"
echo ""
echo "✅ This will give all pages:"
echo "   - Unified navigation"
echo "   - Integrated search box"
echo "   - Consistent design"
echo ""
echo "Press Enter to continue or Ctrl+C to cancel..."
read

for page in "${PAGES[@]}"; do
    if [ -f "$page" ]; then
        echo "✓ $page exists"
    else
        echo "✗ $page not found - skipping"
    fi
done

echo ""
echo "✅ Ready to convert! Each page will:"
echo "   1. Keep its unique content"
echo "   2. Get unified header with search"
echo "   3. Maintain its styling"
echo ""
echo "Run the conversion manually for each page as needed."
