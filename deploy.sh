#!/bin/bash
#
# MediaLog Deployment Script
# Deploys from GitHub to production server
#
# Usage: ./deploy.sh
#

set -e  # Exit on error

echo "🚀 MediaLog Deployment Script"
echo "=============================="
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
REMOTE_HOST="root@157.245.186.58"
REMOTE_PATH="/var/www/html/medialog"
GITHUB_REPO="https://github.com/thecuriobot-debug/medialog.git"
BRANCH="main"

echo -e "${BLUE}Step 1: Pushing local changes to GitHub...${NC}"
git add -A
git status
echo ""
read -p "Commit message: " COMMIT_MSG
if [ -z "$COMMIT_MSG" ]; then
    COMMIT_MSG="Update MediaLog files"
fi
git commit -m "$COMMIT_MSG" || echo "No changes to commit"
git push origin $BRANCH
echo -e "${GREEN}✅ Pushed to GitHub${NC}"
echo ""

echo -e "${BLUE}Step 2: Pulling latest changes on production server...${NC}"
ssh $REMOTE_HOST << EOF
    cd $REMOTE_PATH
    echo "Current directory: \$(pwd)"
    
    # Stash any local changes
    git stash
    
    # Pull latest changes
    git fetch origin
    git reset --hard origin/$BRANCH
    
    echo "Latest commit:"
    git log -1 --oneline
EOF
echo -e "${GREEN}✅ Production server updated${NC}"
echo ""

echo -e "${BLUE}Step 3: Setting permissions...${NC}"
ssh $REMOTE_HOST << EOF
    cd $REMOTE_PATH
    chmod -R 755 .
    chmod +x scripts/*.php 2>/dev/null || true
    chown -R www-data:www-data .
EOF
echo -e "${GREEN}✅ Permissions set${NC}"
echo ""

echo ""
echo "=============================="
echo -e "${GREEN}🎉 Deployment Complete!${NC}"
echo ""
echo "Production URL: http://157.245.186.58/medialog/"
echo "GitHub: https://github.com/thecuriobot-debug/medialog"
echo ""
