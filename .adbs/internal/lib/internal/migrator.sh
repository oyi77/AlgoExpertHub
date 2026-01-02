#!/bin/bash
# Migrator - Handles migration from old structure to new
# Migrates .openspec/ -> .adbs/work/ and .sdd/ -> .adbs/internal/

set -e

PROJECT_ROOT="$(pwd)"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if migration is needed
needs_migration() {
    [ -d ".openspec" ] || [ -d ".sdd" ] || [ -d ".workflow-enforcer" ]
}

# Migrate .openspec/ to .adbs/work/
migrate_openspec() {
    if [ ! -d ".openspec" ]; then
        return
    fi
    
    echo -e "${YELLOW}Migrating OpenSpec structure...${NC}"
    
    # Create new structure
    mkdir -p ".adbs/work"
    mkdir -p ".adbs/archive"
    
    # Migrate active changes
    if [ -d ".openspec/changes" ]; then
        for change in .openspec/changes/*; do
            if [ -d "$change" ]; then
                local change_name=$(basename "$change")
                echo "  • Migrating: $change_name"
                cp -r "$change" ".adbs/work/"
            fi
        done
    fi
    
    # Migrate archive
    if [ -d ".openspec/archive" ]; then
        for archived in .openspec/archive/*; do
            if [ -d "$archived" ]; then
                local archived_name=$(basename "$archived")
                echo "  • Migrating archived: $archived_name"
                cp -r "$archived" ".adbs/archive/"
            fi
        done
    fi
    
    # Backup old structure
    mv ".openspec" ".openspec.backup"
    echo -e "${GREEN}✓ OpenSpec migrated${NC}"
}

# Migrate .sdd/ to .adbs/internal/
migrate_sdd() {
    if [ ! -d ".sdd" ]; then
        return
    fi
    
    echo -e "${YELLOW}Migrating SDD structure...${NC}"
    
    # Create internal directory
    mkdir -p ".adbs/internal"
    
    # Move SDD artifacts
    cp -r ".sdd"/* ".adbs/internal/" 2>/dev/null || true
    
    # Backup old structure
    mv ".sdd" ".sdd.backup"
    echo -e "${GREEN}✓ SDD migrated${NC}"
}

# Migrate .workflow-enforcer/ to .adbs/internal/
migrate_workflow_enforcer() {
    if [ ! -d ".workflow-enforcer" ]; then
        return
    fi
    
    echo -e "${YELLOW}Migrating workflow state...${NC}"
    
    # Create internal directory
    mkdir -p ".adbs/internal"
    
    # Move workflow state
    cp -r ".workflow-enforcer"/* ".adbs/internal/" 2>/dev/null || true
    
    # Backup old structure
    mv ".workflow-enforcer" ".workflow-enforcer.backup"
    echo -e "${GREEN}✓ Workflow state migrated${NC}"
}

# Migrate tasks.json to .adbs/.adbs-tasks.json
migrate_tasks() {
    if [ ! -f "tasks.json" ]; then
        return
    fi
    
    echo -e "${YELLOW}Migrating tasks...${NC}"
    
    # Create .adbs directory
    mkdir -p ".adbs"
    
    # Copy tasks
    cp "tasks.json" ".adbs/.adbs-tasks.json"
    
    # Backup old file
    mv "tasks.json" "tasks.json.backup"
    echo -e "${GREEN}✓ Tasks migrated${NC}"
}

# Main migration
run_migration() {
    echo ""
    echo "ADbS Migration"
    echo "=============="
    echo ""
    echo "This will migrate your project to the new ADbS structure:"
    echo "  • .openspec/     → .adbs/work/"
    echo "  • .sdd/          → .adbs/internal/"
    echo "  • tasks.json     → .adbs/.adbs-tasks.json"
    echo ""
    echo "Old directories will be backed up with .backup suffix"
    echo ""
    
    read -p "Continue? (y/n) " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Migration cancelled"
        exit 0
    fi
    
    echo ""
    
    # Run migrations
    migrate_openspec
    migrate_sdd
    migrate_workflow_enforcer
    migrate_tasks
    
    echo ""
    echo -e "${GREEN}✓ Migration complete!${NC}"
    echo ""
    echo "Your old directories have been backed up:"
    echo "  • .openspec.backup"
    echo "  • .sdd.backup"
    echo "  • .workflow-enforcer.backup"
    echo "  • tasks.json.backup"
    echo ""
    echo "You can safely delete these after verifying the migration."
    echo ""
    echo "Next steps:"
    echo "  1. Run: adbs status"
    echo "  2. Verify your work is listed"
    echo "  3. Continue working: adbs new <name>"
}

# Auto-migrate (no prompt)
auto_migrate() {
    echo "Auto-migrating to new structure..."
    
    migrate_openspec
    migrate_sdd
    migrate_workflow_enforcer
    migrate_tasks
    
    echo "✓ Auto-migration complete"
}

# Main command handler
case "${1:-}" in
    run)
        run_migration
        ;;
    auto)
        auto_migrate
        ;;
    check)
        if needs_migration; then
            echo "Migration needed"
            exit 0
        else
            echo "No migration needed"
            exit 1
        fi
        ;;
    *)
        if needs_migration; then
            run_migration
        else
            echo "No migration needed"
        fi
        ;;
esac
