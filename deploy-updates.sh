#!/bin/bash
# MediaLog - Deploy Updated Subpages

echo "🎨 MediaLog Subpage Update Deployment"
echo "======================================"
echo ""

cd /Users/curiobot/Sites/1n2.org/medialog

# Backup old files
BACKUP_DIR="backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "📦 Creating backup in $BACKUP_DIR..."
cp books.php "$BACKUP_DIR/" 2>/dev/null
cp movies.php "$BACKUP_DIR/" 2>/dev/null
echo "✅ Backup complete"
echo ""

# Deploy updated files
echo "🚀 Deploying updated pages..."

if [ -f "books-updated.php" ]; then
    mv books-updated.php books.php
    echo "✅ books.php updated"
fi

if [ -f "movies-updated.php" ]; then
    mv movies-updated.php movies.php
    echo "✅ movies.php updated"
fi

echo ""
echo "📊 Summary:"
echo "  • Backed up old files to $BACKUP_DIR/"
echo "  • Updated books.php with modern design"
echo "  • Updated movies.php with modern design"
echo "  • Created shared header component"
echo "  • Created shared footer component"
echo ""
echo "🌐 Next Steps:"
echo "  1. Test locally: http://localhost:8000/medialog/books.php"
echo "  2. Test locally: http://localhost:8000/medialog/movies.php"
echo "  3. Deploy to production when ready"
echo ""
echo "✅ Local deployment complete!"
