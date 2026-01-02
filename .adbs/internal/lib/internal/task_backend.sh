#!/bin/bash
# Task Backend - Abstraction layer for task management
# Hides Beads implementation details from users

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Task storage
TASK_FILE="${ADBS_DIR:-.adbs}/.adbs-tasks.json"
SIMPLE_MANAGER="$PROJECT_ROOT/lib/task_manager/simple.sh"

# Check if Beads is available (silent check)
check_beads() {
    local beads_binary="${BEADS_BINARY:-bin/beads/bd}"
    if [ -f "$beads_binary" ] && [ -x "$beads_binary" ]; then
        return 0
    fi
    return 1
}

# Create task
create_task() {
    local description="$1"
    local priority="${2:-medium}"
    local tags="${3:-}"
    
    if [ -z "$description" ]; then
        echo "Error: Task description required"
        echo "Usage: adbs todo <description> [--priority high|medium|low] [--tags tag1,tag2]"
        exit 1
    fi
    
    # Use backend (Beads or internal)
    if check_beads; then
        "$BEADS_BINARY" create "$description" --priority "$priority" ${tags:+--tag "$tags"}
    else
        "$SIMPLE_MANAGER" create "$description" "$priority" "" "" "$tags"
    fi
    
    echo "✓ Added task: $description"
}

# List tasks
list_tasks() {
    local status="$1"
    local priority="$2"
    local tag="$3"
    
    # Use backend
    if check_beads; then
        "$BEADS_BINARY" list ${status:+--status "$status"} ${priority:+--priority "$priority"} ${tag:+--tag "$tag"}
    else
        "$SIMPLE_MANAGER" list "$status" "$priority" "$tag" ""
    fi
}

# Update task
update_task() {
    local id="$1"
    local field="$2"
    local value="$3"
    
    if [ -z "$id" ] || [ -z "$field" ] || [ -z "$value" ]; then
        echo "Error: Task ID, field, and value required"
        echo "Usage: adbs update <id> <field> <value>"
        exit 1
    fi
    
    # Use backend
    if check_beads; then
        case "$field" in
            status)
                "$BEADS_BINARY" update "$id" --status "$value"
                ;;
            priority)
                "$BEADS_BINARY" update "$id" --priority "$value"
                ;;
            *)
                echo "Error: Unknown field '$field'"
                echo "Supported fields: status, priority"
                exit 1
                ;;
        esac
    else
        "$SIMPLE_MANAGER" update "$id" "$field" "$value"
    fi
    
    echo "✓ Updated task $id"
}

# Show task details
show_task() {
    local id="$1"
    
    if [ -z "$id" ]; then
        echo "Error: Task ID required"
        echo "Usage: adbs show <id>"
        exit 1
    fi
    
    # Use backend
    if check_beads; then
        "$BEADS_BINARY" show "$id"
    else
        "$SIMPLE_MANAGER" get "$id"
    fi
}

# Delete task
delete_task() {
    local id="$1"
    
    if [ -z "$id" ]; then
        echo "Error: Task ID required"
        echo "Usage: adbs delete <id>"
        exit 1
    fi
    
    # Use backend
    if check_beads; then
        "$BEADS_BINARY" delete "$id"
    else
        "$SIMPLE_MANAGER" delete "$id"
    fi
    
    echo "✓ Deleted task $id"
}

# Main command handler
case "${1:-}" in
    create|add)
        shift
        
        # Parse arguments
        description=""
        priority="medium"
        tags=""
        
        while [ $# -gt 0 ]; do
            case "$1" in
                --priority)
                    shift
                    priority="$1"
                    shift
                    ;;
                --tags)
                    shift
                    tags="$1"
                    shift
                    ;;
                *)
                    if [ -z "$description" ]; then
                        description="$1"
                    fi
                    shift
                    ;;
            esac
        done
        
        create_task "$description" "$priority" "$tags"
        ;;
    list)
        shift
        
        # Parse filters
        status=""
        priority=""
        tag=""
        
        while [ $# -gt 0 ]; do
            case "$1" in
                --status)
                    shift
                    status="$1"
                    shift
                    ;;
                --priority)
                    shift
                    priority="$1"
                    shift
                    ;;
                --tag)
                    shift
                    tag="$1"
                    shift
                    ;;
                *)
                    shift
                    ;;
            esac
        done
        
        list_tasks "$status" "$priority" "$tag"
        ;;
    update)
        shift
        update_task "$@"
        ;;
    show|get)
        shift
        show_task "$@"
        ;;
    delete|rm)
        shift
        delete_task "$@"
        ;;
    *)
        echo "Unknown task command: ${1:-}"
        echo "Usage: task_backend.sh {create|list|update|show|delete}"
        exit 1
        ;;
esac
