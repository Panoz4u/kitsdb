#!/bin/bash
# Script to clean sensitive data from git history
# IMPORTANT: This will rewrite the entire git history!

set -e  # Exit on error

echo "================================================"
echo "Git History Cleanup Script"
echo "================================================"
echo ""
echo "⚠️  WARNING: This will rewrite ALL git history!"
echo "⚠️  Make sure you have a backup before proceeding."
echo ""

# Check if git-filter-repo is available
if ! command -v git-filter-repo &> /dev/null; then
    echo "❌ git-filter-repo not found!"
    echo ""
    echo "Installing git-filter-repo..."

    # Try to install via pip
    if command -v pip3 &> /dev/null; then
        pip3 install git-filter-repo
    elif command -v pip &> /dev/null; then
        pip install git-filter-repo
    else
        echo "❌ pip not found. Please install git-filter-repo manually:"
        echo "   https://github.com/newren/git-filter-repo"
        exit 1
    fi
fi

echo "✓ git-filter-repo found"
echo ""

# Backup current branch
CURRENT_BRANCH=$(git branch --show-current)
echo "Current branch: $CURRENT_BRANCH"
echo ""

# Show what will be replaced
echo "The following passwords will be removed from history:"
echo "-----------------------------------------------------"
cat expressions.txt | grep "regex:" | sed 's/regex:/  - /' | sed 's/==>.*//'
echo ""

read -p "Continue? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "Step 1: Cleaning git history..."
echo "================================"

# Run git-filter-repo to replace secrets
git filter-repo --replace-text expressions.txt --force

echo ""
echo "✓ History cleaned!"
echo ""
echo "Step 2: Cleanup..."
echo "=================="

# Clean up
git reflog expire --expire=now --all
git gc --prune=now --aggressive

echo ""
echo "✓ Cleanup complete!"
echo ""
echo "================================================"
echo "Next steps:"
echo "================================================"
echo ""
echo "1. Review changes:"
echo "   git log --oneline"
echo ""
echo "2. Force push to GitHub (overwrites remote history):"
echo "   git push origin --force --all"
echo "   git push origin --force --tags"
echo ""
echo "3. IMPORTANT: Change all passwords immediately!"
echo "   - Database passwords"
echo "   - Admin password"
echo ""
echo "4. Mark incidents as resolved on GitGuardian"
echo ""
echo "================================================"
