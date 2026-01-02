#!/bin/bash
# Beads wrapper - attempts to use beads binary, falls back to simple task manager

set -e

BEADS_BINARY="${BEADS_BINARY:-bin/beads/bd}"
SIMPLE_MANAGER="lib/task_manager/simple.sh"
WORKFLOW_DIR="${WORKFLOW_ENFORCER_DIR:-.workflow-enforcer}"

# Check if beads binary exists and is executable
check_beads() {
    if [ -f "$BEADS_BINARY" ] && [ -x "$BEADS_BINARY" ]; then
        return 0
    fi
    return 1
}

# Use beads if available, otherwise fall back to simple manager
if check_beads; then
    # Use beads binary
    exec "$BEADS_BINARY" "$@"
else
    # Fall back to simple task manager
    # Set WORKFLOW_ENFORCER_DIR for simple.sh
    export WORKFLOW_ENFORCER_DIR="$WORKFLOW_DIR"
    
    # Map beads commands to simple.sh commands
    COMMAND="${1:-}"
    shift || true

    case "$COMMAND" in
        create)
            # beads: bd create "description" --priority 1
            # simple: simple.sh create "description" priority parent depends tags
            description=""
            priority="medium"
            parent=""
            depends=""
            tags=""
            
            # If first arg doesn't start with -, assume it's description
            if [[ "$1" != -* ]]; then
                description="$1"
                shift
            fi
            
            while [ $# -gt 0 ]; do
                case "$1" in
                    --priority|-p)
                        shift
                        case "$1" in
                            0|1) priority="high" ;;
                            2) priority="medium" ;;
                            3|4) priority="low" ;;
                            *) priority="$1" ;;
                        esac
                        shift
                        ;;
                    --parent)
                        shift
                        parent="$1"
                        shift
                        ;;
                    --depends|-d)
                        shift
                        if [ -n "$depends" ]; then
                            depends="$depends,$1"
                        else
                            depends="$1"
                        fi
                        shift
                        ;;
                    --tag|-t)
                        shift
                        if [ -n "$tags" ]; then
                            tags="$tags,$1"
                        else
                            tags="$1"
                        fi
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
            
            if [ -z "$description" ]; then
                echo "Error: Description required"
                exit 1
            fi
            
            "$SIMPLE_MANAGER" create "$description" "$priority" "$parent" "$depends" "$tags"
            ;;
            
        list)
            status=""
            priority=""
            tag=""
            desc=""
            
            while [ $# -gt 0 ]; do
                case "$1" in
                    --status|-s)
                        shift
                        status="$1"
                        shift
                        ;;
                    --priority|-p)
                        shift
                        priority="$1"
                        shift
                        ;;
                    --tag|-t)
                        shift
                        tag="$1"
                        shift
                        ;;
                    *)
                        desc="$1"
                        shift
                        ;;
                esac
            done
            
            "$SIMPLE_MANAGER" list "$status" "$priority" "$tag" "$desc"
            ;;
            
        update)
            id="$1"
            shift
            if [ -z "$id" ]; then
                echo "Error: Task ID required"
                exit 1
            fi

            while [ $# -gt 0 ]; do
                case "$1" in
                    --status|-s)
                        shift
                        "$SIMPLE_MANAGER" update "$id" "status" "$1"
                        shift
                        ;;
                    --priority|-p)
                        shift
                        val="$1"
                        case "$val" in
                            0|1) val="high" ;;
                            2) val="medium" ;;
                            3|4) val="low" ;;
                        esac
                        "$SIMPLE_MANAGER" update "$id" "priority" "$val"
                        shift
                        ;;
                    --description|-d)
                        shift
                        "$SIMPLE_MANAGER" update "$id" "description" "$1"
                        shift
                        ;;
                     --depends)
                        shift
                        "$SIMPLE_MANAGER" update "$id" "depends_on" "$1"
                        shift
                        ;;
                    --tags)
                        shift
                        "$SIMPLE_MANAGER" update "$id" "tags" "$1"
                        shift
                        ;;
                    *)
                        shift
                        ;;
                esac
            done
            ;;
            
        show|get)
            "$SIMPLE_MANAGER" get "$@"
            ;;
        delete|rm)
            "$SIMPLE_MANAGER" delete "$@"
            ;;
        export)
            "$SIMPLE_MANAGER" export "$@"
            ;;
        import)
            "$SIMPLE_MANAGER" import "$@"
            ;;
        tree)
            "$SIMPLE_MANAGER" tree "$@"
            ;;
        report)
            "$SIMPLE_MANAGER" report "$@"
            ;;
        depends|dep)
             # Handle 'bd dep add <child> <parent>'
             subcmd="$1"
             shift
             if [ "$subcmd" = "add" ]; then
                 child="$1"
                 parent="$2"
                 "$SIMPLE_MANAGER" update "$child" "parent" "$parent"
             else
                 echo "Unknown depends command: $subcmd"
             fi
             ;;
        help|--help|-h)
            echo "Beads wrapper - using simple task manager (beads binary not found)"
            echo "Usage: bd {create|list|update|show|delete|tree} [args]"
            exit 0
            ;;
        *)
            if [ -z "$COMMAND" ]; then
                 "$SIMPLE_MANAGER" list
            else
                echo "Unknown command: $COMMAND"
                echo "Use --help for usage."
                exit 1
            fi
            ;;
    esac
fi


