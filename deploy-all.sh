#!/bin/bash
echo "🚀 DEPLOYING MEDIALOG TO PRODUCTION"
echo "===================================="

# Core files
echo "📤 Deploying core PHP files..."
scp review.php movies.php books.php root@157.245.186.58:/var/www/html/medialog/

# Overnight session files
echo "📤 Deploying new features..."
scp visualizations.php lists.php list-view.php export.php export-data.php goals.php settings.php root@157.245.186.58:/var/www/html/medialog/

# Includes
echo "📤 Deploying includes..."
scp includes/header.php root@157.245.186.58:/var/www/html/medialog/includes/

# PWA files
echo "📤 Deploying PWA files..."
scp manifest.json sw.js root@157.245.186.58:/var/www/html/medialog/

echo ""
echo "✅ DEPLOYMENT COMPLETE!"
echo "Production URL: http://157.245.186.58/medialog/"
