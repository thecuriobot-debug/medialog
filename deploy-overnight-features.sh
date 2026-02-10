#!/bin/bash
echo "🚀 Deploying Overnight Feature Development..."

# New files to deploy
FILES=(
    "visualizations.php"
    "lists.php"
    "list-view.php"
    "export.php"
    "export-data.php"
    "goals.php"
    "settings.php"
    "manifest.json"
    "sw.js"
    "includes/header.php"
)

# Deploy files
for file in "${FILES[@]}"; do
    echo "📤 Deploying $file..."
    scp "$file" root@157.245.186.58:/var/www/html/medialog/"$file"
done

# Deploy migrations
echo "📤 Deploying migrations..."
scp migrations/*.sql root@157.245.186.58:/var/www/html/medialog/migrations/

echo "✅ Deployment complete!"
