#!/bin/bash
# Interactive UI for ADbS

# Define Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
RESET='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TASK_MANAGER="$SCRIPT_DIR/task_manager/beads_wrapper.sh"

show_menu() {
    clear
    echo "=========================================="
    echo "   ADbS - Ai Dont be Stupid, please!"
    echo "=========================================="
    echo "1. List Tasks"
    echo "2. Create Task"
    echo "3. Update Task Status"
    echo "4. Show Task Tree"
    echo "5. Workflow Status"
    echo "6. Exit"
    echo "=========================================="
}

run_ui() {
    while true; do
        show_menu
        read -p "Select an option (1-6): " choice
        
        case $choice in
            1)
                echo ""
                "$TASK_MANAGER" list
                read -p "Press Enter to continue..."
                ;;
            2)
                echo ""
                read -p "Task Description: " desc
                read -p "Priority (low/medium/high) [medium]: " priority
                priority=${priority:-medium}
                read -p "Tags (comma separated): " tags
                "$TASK_MANAGER" create "$desc" --priority "$priority" "$tags"
                read -p "Press Enter to continue..."
                ;;
            3)
                echo ""
                read -p "Task ID: " id
                echo "1. todo"
                echo "2. in-progress"
                echo "3. done"
                echo "4. blocked"
                read -p "Select status: " status_choice
                case $status_choice in
                    1) status="todo" ;;
                    2) status="in-progress" ;;
                    3) status="done" ;;
                    4) status="blocked" ;;
                    *) status="" ;;
                esac
                
                if [ -n "$status" ]; then
                    "$TASK_MANAGER" update "$id" --status "$status"
                else
                    echo "Invalid status."
                fi
                read -p "Press Enter to continue..."
                ;;
            4)
                echo ""
                "$TASK_MANAGER" tree
                read -p "Press Enter to continue..."
                ;;
            5)
                echo ""
                adbs status 2>/dev/null || ./bin/adbs status
                read -p "Press Enter to continue..."
                ;;
            6)
                echo "Goodbye!"
                exit 0
                ;;
            *)
                echo "Invalid option."
                sleep 1
                ;;
        esac
    done
}
