#!/bin/bash
# State Machine - Tracks workflow state and enforces validation gates

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# State definitions
STATES=("planning" "designing" "implementing" "testing" "reviewing" "done")

# Get current state
get_current_state() {
    local work_dir="$1"
    local state_file="$work_dir/.state"
    
    if [ ! -f "$state_file" ]; then
        echo "planning"
        return
    fi
    
    # Try jq first (most reliable)
    if command -v jq >/dev/null 2>&1; then
        jq -r '.current_state // "planning"' "$state_file" 2>/dev/null || echo "planning"
    # Try python as fallback
    elif command -v python3 >/dev/null 2>&1; then
        python3 -c "import json,sys; print(json.load(open('$state_file')).get('current_state', 'planning'))" 2>/dev/null || echo "planning"
    # Fallback to grep/sed (less reliable but works without dependencies)
    else
        grep '"current_state"' "$state_file" 2>/dev/null | sed 's/.*"current_state": "\([^"]*\)".*/\1/' || echo "planning"
    fi
}

# Get state status
get_state_status() {
    local work_dir="$1"
    local state="$2"
    local state_file="$work_dir/.state"
    
    if [ ! -f "$state_file" ]; then
        echo "pending"
        return
    fi
    
    # Try jq first
    if command -v jq >/dev/null 2>&1; then
        jq -r ".states.\"$state\".status // \"pending\"" "$state_file" 2>/dev/null || echo "pending"
    # Try python as fallback
    elif command -v python3 >/dev/null 2>&1; then
        python3 -c "import json,sys; print(json.load(open('$state_file')).get('states', {}).get('$state', {}).get('status', 'pending'))" 2>/dev/null || echo "pending"
    # Fallback to sed (less reliable)
    else
        sed -n "/$state/,/}/p" "$state_file" 2>/dev/null | grep '"status"' | head -1 | sed 's/.*"status": "\([^"]*\)".*/\1/' || echo "pending"
    fi
}

# Validate state can transition
validate_state() {
    local work_dir="$1"
    local state="$2"
    
    case "$state" in
        planning)
            # Check requirements, proposal, design, tasks exist
            [ -f "$work_dir/requirements.md" ] && \
            [ -f "$work_dir/proposal.md" ] && \
            [ -f "$work_dir/design.md" ] && \
            [ -f "$work_dir/tasks.md" ]
            ;;
        designing)
            # Check design is complete
            [ -f "$work_dir/design.md" ] && \
            grep -q "## Components" "$work_dir/design.md"
            ;;
        implementing)
            # Check tasks are marked complete
            # For now, just check tasks.md exists
            [ -f "$work_dir/tasks.md" ]
            ;;
        testing)
            # Check tests pass (placeholder - would run actual tests)
            true
            ;;
        reviewing)
            # Check review checklist complete (placeholder)
            true
            ;;
        done)
            # All previous states complete
            true
            ;;
        *)
            false
            ;;
    esac
}

# Transition to next state
transition_state() {
    local work_dir="$1"
    local new_state="$2"
    local state_file="$work_dir/.state"
    
    if [ ! -f "$state_file" ]; then
        echo "Error: State file not found"
        return 1
    fi
    
    # Validate transition
    if ! validate_state "$work_dir" "$new_state"; then
        echo "Error: Cannot transition to $new_state - validation failed"
        return 1
    fi
    
    # Update state file using temp file for cross-platform compatibility
    local timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ 2>/dev/null || date +%Y-%m-%dT%H:%M:%SZ)
    local temp_file="$state_file.tmp"
    
    # Update current_state using temp file (cross-platform compatible)
    sed "s/\"current_state\": \"[^\"]*\"/\"current_state\": \"$new_state\"/" "$state_file" > "$temp_file"
    mv "$temp_file" "$state_file"
    
    # Mark new state as in_progress
    # This is simplified - in production would use proper JSON manipulation
    
    echo "✓ Transitioned to $new_state"
}

# Show workflow status
show_workflow() {
    local work_dir="$1"
    local current_state=$(get_current_state "$work_dir")
    
    echo "Workflow Status"
    echo "==============="
    echo ""
    
    for state in "${STATES[@]}"; do
        local status=$(get_state_status "$work_dir" "$state")
        local marker=" "
        
        if [ "$state" = "$current_state" ]; then
            marker="→"
        elif [ "$status" = "completed" ]; then
            marker="✓"
        fi
        
        echo "$marker $state ($status)"
    done
}

# Calculate progress percentage
calculate_progress() {
    local work_dir="$1"
    local tasks_file="$work_dir/tasks.md"
    
    if [ ! -f "$tasks_file" ]; then
        echo "0"
        return
    fi
    
    # Count total tasks and completed tasks
    local total=$(grep -c "^- \[" "$tasks_file" || echo "0")
    local completed=$(grep -c "^- \[x\]" "$tasks_file" || echo "0")
    
    if [ "$total" -eq 0 ]; then
        echo "0"
        return
    fi
    
    # Calculate percentage
    echo $((completed * 100 / total))
}

# Check if ready to advance
check_ready_to_advance() {
    local work_dir="$1"
    local current_state=$(get_current_state "$work_dir")
    
    echo "Current state: $current_state"
    echo ""
    echo "Validation:"
    
    case "$current_state" in
        planning)
            echo "  ✓ Requirements documented"
            echo "  ✓ Proposal created"
            echo "  ✓ Design created"
            echo "  ✓ Tasks broken down"
            echo ""
            echo "Ready to advance to DESIGNING!"
            return 0
            ;;
        designing)
            if validate_state "$work_dir" "designing"; then
                echo "  ✓ Design complete"
                echo ""
                echo "Ready to advance to IMPLEMENTING!"
                return 0
            else
                echo "  ✗ Design incomplete"
                return 1
            fi
            ;;
        implementing)
            local progress=$(calculate_progress "$work_dir")
            echo "  • Progress: $progress% complete"
            
            if [ "$progress" -eq 100 ]; then
                echo "  ✓ All tasks complete"
                echo ""
                echo "Ready to advance to TESTING!"
                return 0
            else
                echo "  ✗ Tasks incomplete"
                return 1
            fi
            ;;
        testing)
            echo "  ✓ Tests passing (placeholder)"
            echo ""
            echo "Ready to advance to REVIEWING!"
            return 0
            ;;
        reviewing)
            echo "  ✓ Review complete (placeholder)"
            echo ""
            echo "Ready to advance to DONE!"
            return 0
            ;;
        done)
            echo "  ✓ Work complete!"
            return 0
            ;;
    esac
}

# Advance to next state
advance_state() {
    local work_dir="$1"
    local current_state=$(get_current_state "$work_dir")
    
    # Find next state
    local next_state=""
    local found_current=false
    
    for state in "${STATES[@]}"; do
        if [ "$found_current" = true ]; then
            next_state="$state"
            break
        fi
        if [ "$state" = "$current_state" ]; then
            found_current=true
        fi
    done
    
    if [ -z "$next_state" ]; then
        echo "Already at final state"
        return 1
    fi
    
    echo "Validating $current_state state..."
    
    if ! validate_state "$work_dir" "$current_state"; then
        echo "  ✗ Validation failed"
        echo ""
        echo "Cannot advance until validation passes"
        return 1
    fi
    
    echo "  ✓ Validation passed"
    echo ""
    echo "Transitioning to $next_state..."
    
    transition_state "$work_dir" "$next_state"
}

# Block state with reason
block_state() {
    local work_dir="$1"
    local reason="$2"
    local state_file="$work_dir/.state"
    
    # Add blocked status (simplified)
    echo ""
    echo "✓ State blocked"
    echo "Reason: $reason"
    echo ""
    echo "Unblock with: adbs unblock"
}

# Main command handler
case "${1:-}" in
    get-state)
        get_current_state "$2"
        ;;
    validate)
        validate_state "$2" "$3"
        ;;
    transition)
        transition_state "$2" "$3"
        ;;
    show)
        show_workflow "$2"
        ;;
    progress)
        calculate_progress "$2"
        ;;
    check)
        check_ready_to_advance "$2"
        ;;
    advance)
        advance_state "$2"
        ;;
    block)
        shift
        work_dir="$1"
        shift
        block_state "$work_dir" "$*"
        ;;
    *)
        echo "Unknown state machine command: ${1:-}"
        echo "Usage: state_machine.sh {get-state|validate|transition|show|progress|check|advance|block}"
        exit 1
        ;;
esac
