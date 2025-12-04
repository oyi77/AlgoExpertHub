#!/bin/bash

# Switch Branch & Clear Cache Script
# Usage: ./switch-branch.sh <branch-name>

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if branch name is provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: Branch name is required${NC}"
    echo "Usage: ./switch-branch.sh <branch-name>"
    echo "Example: ./switch-branch.sh main"
    exit 1
fi

BRANCH_NAME=$1

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Switching to branch: ${GREEN}$BRANCH_NAME${NC}"
echo -e "${YELLOW}========================================${NC}"

# Check if branch exists locally
echo -e "\n${YELLOW}[1/4] Checking and switching branch...${NC}"

if ! git rev-parse --verify "$BRANCH_NAME" > /dev/null 2>&1; then
    echo -e "${YELLOW}Branch '$BRANCH_NAME' not found locally. Checking remote...${NC}"
    
    # Fetch latest from remote
    echo -e "${YELLOW}Fetching latest from remote...${NC}"
    git fetch --all --prune
    
    # Check if branch exists on remote
    REMOTE_BRANCH=""
    for remote in $(git remote); do
        if git ls-remote --heads "$remote" "$BRANCH_NAME" > /dev/null 2>&1; then
            REMOTE_BRANCH="$remote/$BRANCH_NAME"
            echo -e "${GREEN}✓ Found branch on remote: $REMOTE_BRANCH${NC}"
            break
        fi
    done
    
    if [ -z "$REMOTE_BRANCH" ]; then
        echo -e "${RED}Error: Branch '$BRANCH_NAME' does not exist locally or on remote${NC}"
        echo ""
        echo "Available local branches:"
        git branch
        echo ""
        echo "Available remote branches:"
        git branch -r
        exit 1
    fi
    
    # Create local branch tracking remote branch
    echo -e "${YELLOW}Creating local branch tracking $REMOTE_BRANCH...${NC}"
    git checkout -b "$BRANCH_NAME" "$REMOTE_BRANCH"
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed to create and checkout branch${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ Created and switched to $BRANCH_NAME (tracking $REMOTE_BRANCH)${NC}"
else
    # Branch exists locally, just switch to it
    git checkout "$BRANCH_NAME"
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed to switch branch${NC}"
        exit 1
    fi
    
    # Pull latest changes if tracking remote branch
    if git rev-parse --abbrev-ref --symbolic-full-name "$BRANCH_NAME@{u}" > /dev/null 2>&1; then
        echo -e "${YELLOW}Pulling latest changes...${NC}"
        git pull --ff-only
    fi
    
    echo -e "${GREEN}✓ Switched to $BRANCH_NAME${NC}"
fi

# Clear application cache
echo -e "\n${YELLOW}[2/4] Clearing application cache...${NC}"
php artisan cache:clear
echo -e "${GREEN}✓ Application cache cleared${NC}"

# Clear view cache
echo -e "\n${YELLOW}[3/4] Clearing compiled views...${NC}"
php artisan view:clear
echo -e "${GREEN}✓ Compiled views cleared${NC}"

# Clear config cache
echo -e "\n${YELLOW}[4/4] Clearing configuration cache...${NC}"
php artisan config:clear
echo -e "${GREEN}✓ Configuration cache cleared${NC}"

# Optional: Clear route cache if exists
if [ -f "bootstrap/cache/routes-v7.php" ]; then
    echo -e "\n${YELLOW}[Bonus] Clearing route cache...${NC}"
    php artisan route:clear
    echo -e "${GREEN}✓ Route cache cleared${NC}"
fi

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Successfully switched to: $BRANCH_NAME${NC}"
echo -e "${GREEN}✓ All caches cleared${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "\n${YELLOW}Current branch:${NC} $(git branch --show-current)"
echo -e "${YELLOW}Latest commit:${NC} $(git log -1 --oneline)"
echo ""

