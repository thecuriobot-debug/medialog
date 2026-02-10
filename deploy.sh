#!/bin/bash
# MediaLog Deployment Script

echo "🚀 Deploying MediaLog to Production..."

SERVER="root@157.245.186.58"
REMOTE_DIR="/var/www/html/medialog"
LOCAL_DIR="/Users/curiobot/Sites/1n2.org/medialog"

cd $LOCAL_DIR

# Deploy all PHP files
echo "📦 Deploying PHP files..."
scp *.php $SERVER:$REMOTE_DIR/

# Deploy config
echo "⚙️  Deploying config..."
scp config.php $SERVER:$REMOTE_DIR/

# Deploy includes
echo "📁 Deploying includes..."
scp -r includes/ $SERVER:$REMOTE_DIR/

echo "✅ Deployment complete!"
echo "🌐 Live at: http://157.245.186.58/medialog/"
