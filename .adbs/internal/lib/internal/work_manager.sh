#!/bin/bash
# Work Manager - Abstraction layer for work items
# Hides OpenSpec/SDD implementation details from users

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Internal engines (hidden from users)
OPENSPEC_ENGINE="$PROJECT_ROOT/lib/internal/openspec_engine.sh"
VALIDATOR="$PROJECT_ROOT/lib/internal/validator.sh"
WORKFLOW_GENERATOR="$PROJECT_ROOT/lib/internal/workflow_generator.sh"
STATE_MACHINE="$PROJECT_ROOT/lib/internal/state_machine.sh"

# Work directory (new structure)
WORK_DIR="${ADBS_DIR:-.adbs}/work"
ARCHIVE_DIR="${ADBS_DIR:-.adbs}/archive"
INTERNAL_DIR="${ADBS_DIR:-.adbs}/internal"

# Source workflow generator if it exists
if [ -f "$WORKFLOW_GENERATOR" ]; then
    source "$WORKFLOW_GENERATOR"
fi

# Detect project complexity and choose workflow
detect_workflow() {
    # Always use OpenSpec (simple and covers 90% of cases)
    # Future enhancement: Add SDD detection based on .sdd directory presence
    echo "openspec"
}

# Create new work item
create_work() {
    local name="$1"
    local ai_generate="${2:-false}"
    
    if [ -z "$name" ]; then
        echo "Error: Work name required"
        echo "Usage: adbs new <name> [--ai-generate]"
        exit 1
    fi
    
    # Ensure work directory exists
    mkdir -p "$WORK_DIR"
    
    # Detect workflow (currently always OpenSpec)
    local workflow=$(detect_workflow)
    
    # Generate work ID (date-based)
    local work_id="$(date +%Y-%m-%d)-${name}"
    local work_path="$WORK_DIR/$work_id"
    
    # Check if already exists
    if [ -d "$work_path" ]; then
        echo "Error: Work '$name' already exists"
        echo "Use 'adbs show $name' to view it"
        exit 1
    fi
    
    # Create work directory
    mkdir -p "$work_path"
    
    # Check if AI generation requested
    if [ "$ai_generate" = "true" ] || [ "$ai_generate" = "--ai-generate" ]; then
        # Use workflow generator
        if [ -f "$WORKFLOW_GENERATOR" ]; then
            if ! generate_workflow "$name" "$work_path"; then
                echo "Error: Workflow generation failed"
                # Clean up incomplete work directory
                rm -rf "$work_path"
                exit 1
            fi
            
            echo ""
            echo "Created work: $name"
            echo "Location: $work_path"
            echo ""
            echo "Current state: PLANNING"
            echo "Next step: Review generated workflow"
            echo ""
            echo "Commands:"
            echo "  adbs show $name              # View proposal"
            echo "  adbs workflow $name          # View workflow status"
            echo "  adbs progress $name          # Check if ready to advance"
            echo "  adbs advance $name           # Move to next state"
            return 0
        else
            echo "Warning: Workflow generator not found, using simple mode"
            ai_generate="false"
        fi
    fi
    
    # If not AI-generated, create simple proposal
    if [ "$ai_generate" = "false" ]; then
        cat > "$work_path/proposal.md" <<EOF
# $name

## What are we building?

[Describe what you want to build here]

## Why?

[Explain the motivation or problem this solves]

## How?

[Outline the approach or key steps]

## Done when...

- [ ] [List completion criteria]

EOF
        
        echo "✓ Started new work: $name"
        echo ""
        echo "Next steps:"
        echo "  1. Edit the work plan: $work_path/proposal.md"
        echo "  2. Check status: adbs status"
        echo "  3. Mark done: adbs done \"$name\""
    fi
}


# List all active work
list_work() {
    local filter="$1"
    
    if [ ! -d "$WORK_DIR" ]; then
        echo "No active work"
        echo "Start something new: adbs new <name>"
        return
    fi
    
    local work_count=0
    
    echo "Active Work:"
    echo ""
    
    for work_path in "$WORK_DIR"/*; do
        if [ -d "$work_path" ]; then
            local work_id=$(basename "$work_path")
            # Extract name (remove date prefix)
            local work_name=$(echo "$work_id" | sed 's/^[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}-//')
            
            # Get first line of proposal as description
            local desc=""
            if [ -f "$work_path/proposal.md" ]; then
                desc=$(grep -m 1 "^# " "$work_path/proposal.md" | sed 's/^# //')
            fi
            
            echo "  • $work_name"
            if [ -n "$desc" ] && [ "$desc" != "$work_name" ]; then
                echo "    $desc"
            fi
            
            work_count=$((work_count + 1))
        fi
    done
    
    if [ $work_count -eq 0 ]; then
        echo "  (none)"
        echo ""
        echo "Start something new: adbs new <name>"
    fi
}

# Show work details
show_work() {
    local name="$1"
    
    if [ -z "$name" ]; then
        echo "Error: Work name required"
        echo "Usage: adbs show <name>"
        exit 1
    fi
    
    # Find work by name (fuzzy match)
    local work_path=""
    for path in "$WORK_DIR"/*-"$name"; do
        if [ -d "$path" ]; then
            work_path="$path"
            break
        fi
    done
    
    if [ -z "$work_path" ] || [ ! -d "$work_path" ]; then
        echo "Error: Work '$name' not found"
        echo "Use 'adbs list' to see active work"
        exit 1
    fi
    
    # Show proposal content
    if [ -f "$work_path/proposal.md" ]; then
        cat "$work_path/proposal.md"
    else
        echo "No details available for '$name'"
    fi
}

# Mark work as complete
complete_work() {
    local name="$1"
    
    if [ -z "$name" ]; then
        echo "Error: Work name required"
        echo "Usage: adbs done <name>"
        exit 1
    fi
    
    # Find work by name
    local work_path=""
    for path in "$WORK_DIR"/*-"$name"; do
        if [ -d "$path" ]; then
            work_path="$path"
            break
        fi
    done
    
    if [ -z "$work_path" ] || [ ! -d "$work_path" ]; then
        echo "Error: Work '$name' not found"
        echo "Use 'adbs list' to see active work"
        exit 1
    fi
    
    # Ensure archive directory exists
    mkdir -p "$ARCHIVE_DIR"
    
    # Move to archive
    local work_id=$(basename "$work_path")
    mv "$work_path" "$ARCHIVE_DIR/"
    
    echo "✓ Completed: $name"
    echo ""
    echo "Archived to: $ARCHIVE_DIR/$work_id"
}

# Show status of all work
show_status() {
    echo "ADbS Status"
    echo "==========="
    echo ""
    
    # Count active work using shell globbing (more efficient than find)
    local active_count=0
    if [ -d "$WORK_DIR" ]; then
        for dir in "$WORK_DIR"/*; do
            [ -d "$dir" ] && active_count=$((active_count + 1))
        done
    fi
    
    # Count archived work using shell globbing
    local archive_count=0
    if [ -d "$ARCHIVE_DIR" ]; then
        for dir in "$ARCHIVE_DIR"/*; do
            [ -d "$dir" ] && archive_count=$((archive_count + 1))
        done
    fi
    
    echo "Active work: $active_count"
    echo "Completed: $archive_count"
    echo ""
    
    if [ $active_count -gt 0 ]; then
        list_work
    else
        echo "No active work"
        echo "Start something new: adbs new <name>"
    fi
}

# Main command handler
case "${1:-}" in
    create|new)
        shift
        create_work "$@"
        ;;
    list)
        shift
        list_work "$@"
        ;;
    show)
        shift
        show_work "$@"
        ;;
    complete|done)
        shift
        complete_work "$@"
        ;;
    status)
        show_status
        ;;
    *)
        echo "Unknown work command: ${1:-}"
        echo "Usage: work_manager.sh {create|list|show|complete|status}"
        exit 1
        ;;
esac
