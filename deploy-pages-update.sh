#!/bin/bash
# MediaLog - Deploy Updated Pages to Production

set -e

echo "🚀 Deploying updated MediaLog pages to production..."
echo ""

cd /Users/curiobot/Sites/1n2.org/medialog

# List files to deploy
echo "📦 Files to deploy:"
echo "  - authors.php (✨ Updated with polish & stats)"
echo "  - directors.php (✨ Updated with polish & stats)"
echo "  - stats.php (keeping existing)"
echo "  - insights.php (keeping existing)"
echo "  - movie-insights.php (keeping existing)"
echo ""

# Deploy to production
echo "📤 Uploading to 157.245.186.58..."

scp authors.php root@157.245.186.58:/var/www/html/medialog/
scp directors.php root@157.245.186.58:/var/www/html/medialog/

echo ""
echo "✅ Deployment complete!"
echo ""
echo "🌐 Live URLs:"
echo "  - Authors: http://1n2.org/medialog/authors.php"
echo "  - Directors: http://1n2.org/medialog/directors.php"
echo "  - Stats: http://1n2.org/medialog/stats.php"
echo "  - Insights: http://1n2.org/medialog/insights.php"
echo ""
echo "🎉 Pages updated on production server!"
