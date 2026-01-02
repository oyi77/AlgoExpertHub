#!/bin/bash
# Simple JSON-based task manager (pure shell implementation)
# Alternative to Beads when Go dependency is not available

set -e

TASKS_FILE="${WORKFLOW_ENFORCER_DIR:-.workflow-enforcer}/tasks.json"
JQ_CMD=""

# Check dependencies once
HAS_JQ=0
HAS_PYTHON3=0
if command -v jq &> /dev/null; then HAS_JQ=1; fi
if command -v python3 &> /dev/null; then HAS_PYTHON3=1; fi

# Check if jq is available, otherwise use awk/sed
if [ "$HAS_JQ" -eq 1 ]; then
    JQ_CMD="jq"
elif [ "$HAS_PYTHON3" -eq 1 ]; then
    JQ_CMD="python3"
else
    JQ_CMD="awk"
fi

# Initialize tasks file if it doesn't exist
init_tasks() {
    local dir=$(dirname "$TASKS_FILE")
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
    fi
    
    if [ ! -f "$TASKS_FILE" ]; then
        echo '{"tasks":[],"next_id":1}' > "$TASKS_FILE"
    fi
    # Ensure tasks have all new fields
    if [ "$JQ_CMD" = "jq" ]; then
        jq '.tasks[] |= (. + {tags: (.tags // []), depends_on: (.depends_on // []), comments: (.comments // [])})' "$TASKS_FILE" > "${TASKS_FILE}.tmp" 2>/dev/null && mv "${TASKS_FILE}.tmp" "$TASKS_FILE" || true
    fi
}

# Generate a short random ID (similar to beads format)
generate_id() {
    if [ -e /dev/urandom ] && command -v md5sum >/dev/null; then
        # Fast generation using system random source (Linux/macOS)
        head -c 10 /dev/urandom | md5sum | cut -c 1-6
    elif [ "$HAS_PYTHON3" -eq 1 ]; then
        python3 -c "import uuid; print(str(uuid.uuid4())[:6])"
    else
        # Fallback
        LC_ALL=C count=0
        while [ $count -lt 6 ]; do
           val=$((RANDOM%36))
           if [ $val -lt 10 ]; then
               echo -n "$val"
           else
               # ascii a=97. val-10+97
               printf \\$(printf '%03o' $((val-10+97)))
           fi
           count=$((count+1))
        done
        echo ""
    fi
}

# Generate hierarchical task ID
generate_hierarchical_id() {
    local parent="$1"
    
    if [ -z "$parent" ]; then
        echo "task-$(generate_id)"
    else
        # Count existing children
        local child_count=0
        if [ "$JQ_CMD" = "jq" ]; then
            child_count=$(jq -r --arg parent "$parent" '[.tasks[] | select(.parent == $parent) | .id] | length' "$TASKS_FILE" 2>/dev/null || echo "0")
        elif [ "$JQ_CMD" = "python3" ]; then
            child_count=$(python3 -c "import json; data=json.load(open('$TASKS_FILE')); print(len([t for t in data['tasks'] if t.get('parent') == '$parent']))" 2>/dev/null || echo "0")
        fi
        echo "${parent}.$((child_count + 1))"
    fi
}

# Create a new task
create_task() {
    local description="$1"
    local priority="${2:-medium}"
    local parent="${3:-}"
    local depends_on="${4:-}"
    local tags="${5:-}"
    
    init_tasks
    
    local id=$(generate_hierarchical_id "$parent")
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ" 2>/dev/null || date -u +"%Y-%m-%d %H:%M:%S")
    
    # Parse tags (comma-separated)
    local tags_array="[]"
    if [ -n "$tags" ]; then
        if [ "$JQ_CMD" = "jq" ]; then
            tags_array=$(echo "$tags" | jq -R 'split(",") | map(gsub("^\\s+|\\s+$"; ""))')
        elif [ "$JQ_CMD" = "python3" ]; then
            tags_array=$(python3 -c "import json, sys; print(json.dumps([t.strip() for t in '$tags'.split(',')]))")
        fi
    fi
    
    # Parse depends_on (comma-separated)
    local depends_array="[]"
    if [ -n "$depends_on" ]; then
        if [ "$JQ_CMD" = "jq" ]; then
            depends_array=$(echo "$depends_on" | jq -R 'split(",") | map(gsub("^\\s+|\\s+$"; ""))')
        elif [ "$JQ_CMD" = "python3" ]; then
            depends_array=$(python3 -c "import json, sys; print(json.dumps([t.strip() for t in '$depends_on'.split(',')]))")
        fi
    fi
    
    if [ "$JQ_CMD" = "jq" ]; then
        local task_json=$(jq -n \
            --arg id "$id" \
            --arg desc "$description" \
            --arg priority "$priority" \
            --arg parent "${parent:-null}" \
            --argjson tags "$tags_array" \
            --argjson depends "$depends_array" \
            --arg timestamp "$timestamp" \
            '{
                id: $id,
                description: $desc,
                status: "todo",
                priority: $priority,
                parent: (if $parent == "null" then null else $parent end),
                depends_on: $depends,
                tags: $tags,
                comments: [],
                created_at: $timestamp,
                updated_at: $timestamp
            }')
        jq --argjson task "$task_json" '.tasks += [$task] | .next_id += 1' "$TASKS_FILE" > "${TASKS_FILE}.tmp" && mv "${TASKS_FILE}.tmp" "$TASKS_FILE"
    elif [ "$JQ_CMD" = "python3" ]; then
        python3 <<PYTHON
import json
from datetime import datetime

with open("$TASKS_FILE", "r") as f:
    data = json.load(f)

task = {
    "id": "$id",
    "description": "$description",
    "status": "todo",
    "priority": "$priority",
    "parent": "$parent" if "$parent" else None,
    "depends_on": [t.strip() for t in "$depends_on".split(",")] if "$depends_on" else [],
    "tags": [t.strip() for t in "$tags".split(",")] if "$tags" else [],
    "comments": [],
    "created_at": datetime.utcnow().isoformat() + "Z",
    "updated_at": datetime.utcnow().isoformat() + "Z"
}

data["tasks"].append(task)
data["next_id"] += 1

with open("$TASKS_FILE", "w") as f:
    json.dump(data, f, indent=2)
PYTHON
    else
        echo "Warning: jq or python3 not available. Task creation may be limited."
        return 1
    fi
    
    echo "$id"
}

# Add comment to task
add_comment() {
    local id="$1"
    local comment="$2"
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ" 2>/dev/null || date -u +"%Y-%m-%d %H:%M:%S")
    
    init_tasks
    
    if [ "$JQ_CMD" = "jq" ]; then
        jq --arg id "$id" --arg comment "$comment" --arg timestamp "$timestamp" \
           '(.tasks[] | select(.id == $id) | .comments) += [{"text": $comment, "created_at": $timestamp}] | 
            (.tasks[] | select(.id == $id) | .updated_at) = (now | todateiso8601)' \
           "$TASKS_FILE" > "${TASKS_FILE}.tmp" && mv "${TASKS_FILE}.tmp" "$TASKS_FILE"
    elif [ "$JQ_CMD" = "python3" ]; then
        python3 <<PYTHON
import json
from datetime import datetime

with open("$TASKS_FILE", "r") as f:
    data = json.load(f)

for task in data["tasks"]:
    if task["id"] == "$id":
        if "comments" not in task:
            task["comments"] = []
        task["comments"].append({
            "text": "$comment",
            "created_at": datetime.utcnow().isoformat() + "Z"
        })
        task["updated_at"] = datetime.utcnow().isoformat() + "Z"
        break

with open("$TASKS_FILE", "w") as f:
    json.dump(data, f, indent=2)
PYTHON
    fi
}

# Add tag to task
add_tag() {
    local id="$1"
    local tag="$2"
    
    init_tasks
    
    if [ "$JQ_CMD" = "jq" ]; then
        jq --arg id "$id" --arg tag "$tag" \
           '(.tasks[] | select(.id == $id) | .tags) |= (if . then (. + [$tag] | unique) else [$tag] end) | 
            (.tasks[] | select(.id == $id) | .updated_at) = (now | todateiso8601)' \
           "$TASKS_FILE" > "${TASKS_FILE}.tmp" && mv "${TASKS_FILE}.tmp" "$TASKS_FILE"
    elif [ "$JQ_CMD" = "python3" ]; then
        python3 <<PYTHON
import json
from datetime import datetime

with open("$TASKS_FILE", "r") as f:
    data = json.load(f)

for task in data["tasks"]:
    if task["id"] == "$id":
        if "tags" not in task:
            task["tags"] = []
        if "$tag" not in task["tags"]:
            task["tags"].append("$tag")
        task["updated_at"] = datetime.utcnow().isoformat() + "Z"
        break

with open("$TASKS_FILE", "w") as f:
    json.dump(data, f, indent=2)
PYTHON
    fi
}

# Update a task
update_task() {
    local id="$1"
    local field="$2"
    local value="$3"
    
    init_tasks
    
    # Handle special fields
    if [ "$field" = "tags" ] || [ "$field" = "depends_on" ]; then
        # Parse comma-separated values
        if [ "$JQ_CMD" = "jq" ]; then
            local array_value=$(echo "$value" | jq -R 'split(",") | map(gsub("^\\s+|\\s+$"; ""))')
            jq --arg id "$id" --arg field "$field" --argjson val "$array_value" \
               '(.tasks[] | select(.id == $id) | .[$field]) = $val | 
                (.tasks[] | select(.id == $id) | .updated_at) = (now | todateiso8601)' \
               "$TASKS_FILE" > "${TASKS_FILE}.tmp" && mv "${TASKS_FILE}.tmp" "$TASKS_FILE"
        elif [ "$JQ_CMD" = "python3" ]; then
            python3 <<PYTHON
import json
from datetime import datetime

with open("$TASKS_FILE", "r") as f:
    data = json.load(f)

for task in data["tasks"]:
    if task["id"] == "$id":
        task["$field"] = [t.strip() for t in "$value".split(",")]
        task["updated_at"] = datetime.utcnow().isoformat() + "Z"
        break

with open("$TASKS_FILE", "w") as f:
    json.dump(data, f, indent=2)
PYTHON
        fi
    else
        # Regular field update
        if [ "$JQ_CMD" = "jq" ]; then
            jq --arg id "$id" --arg field "$field" --arg value "$value" \
               '(.tasks[] | select(.id == $id) | .[$field]) = $value | 
                (.tasks[] | select(.id == $id) | .updated_at) = (now | todateiso8601)' \
               "$TASKS_FILE" > "${TASKS_FILE}.tmp" && mv "${TASKS_FILE}.tmp" "$TASKS_FILE"
        elif [ "$JQ_CMD" = "python3" ]; then
            python3 <<PYTHON
import json
from datetime import datetime

with open("$TASKS_FILE", "r") as f:
    data = json.load(f)

for task in data["tasks"]:
    if task["id"] == "$id":
        task["$field"] = "$value"
        task["updated_at"] = datetime.utcnow().isoformat() + "Z"
        break

with open("$TASKS_FILE", "w") as f:
    json.dump(data, f, indent=2)
PYTHON
        fi
    fi
}

# Search tasks
search_tasks() {
    local status_filter="${1:-}"
    local priority_filter="${2:-}"
    local tag_filter="${3:-}"
    local desc_filter="${4:-}"
    
    init_tasks
    
    if [ "$JQ_CMD" = "jq" ]; then
        local filter=".tasks[]"
        local conditions=()
        
        [ -n "$status_filter" ] && conditions+=(".status == \"$status_filter\"")
        [ -n "$priority_filter" ] && conditions+=(".priority == \"$priority_filter\"")
        [ -n "$tag_filter" ] && conditions+=("(.tags // []) | index(\"$tag_filter\") != null")
        [ -n "$desc_filter" ] && conditions+=("(.description | ascii_downcase | contains(\"$desc_filter\" | ascii_downcase))")
        
        if [ ${#conditions[@]} -gt 0 ]; then
            local filter_expr=$(IFS=" and "; echo "${conditions[*]}")
            jq --arg status "$status_filter" --arg priority "$priority_filter" --arg tag "$tag_filter" --arg desc "$desc_filter" \
               ".tasks[] | select($filter_expr)" "$TASKS_FILE"
        else
            jq '.tasks[]' "$TASKS_FILE"
        fi
    elif [ "$JQ_CMD" = "python3" ]; then
        python3 <<PYTHON
import json
import sys

with open("$TASKS_FILE", "r") as f:
    data = json.load(f)

for task in data["tasks"]:
    if "$status_filter" and task.get("status") != "$status_filter":
        continue
    if "$priority_filter" and task.get("priority") != "$priority_filter":
        continue
    if "$tag_filter":
        tags = task.get("tags", [])
        if "$tag_filter" not in tags:
            continue
    if "$desc_filter":
        desc = task.get("description", "").lower()
        if "$desc_filter".lower() not in desc:
            continue
    print(json.dumps(task, indent=2))
PYTHON
    else
        # Basic fallback
        grep -A 10 "\"id\"" "$TASKS_FILE" || echo "[]"
    fi
}

# List tasks (alias for search)
list_tasks() {
    search_tasks "$@"
}

# Get task by ID
get_task() {
    local id="$1"
    
    init_tasks
    
    if [ "$JQ_CMD" = "jq" ]; then
        jq --arg id "$id" '.tasks[] | select(.id == $id)' "$TASKS_FILE"
    elif [ "$JQ_CMD" = "python3" ]; then
        python3 <<PYTHON
import json
import sys

with open("$TASKS_FILE", "r") as f:
    data = json.load(f)

for task in data["tasks"]:
    if task["id"] == "$id":
        print(json.dumps(task, indent=2))
        sys.exit(0)

sys.exit(1)
PYTHON
    else
        grep -A 10 "\"id\":\"$id\"" "$TASKS_FILE" || echo "{}"
    fi
}

# Delete a task
delete_task() {
    local id="$1"
    
    init_tasks
    
    if [ "$JQ_CMD" = "jq" ]; then
        jq --arg id "$id" 'del(.tasks[] | select(.id == $id))' "$TASKS_FILE" > "${TASKS_FILE}.tmp" && mv "${TASKS_FILE}.tmp" "$TASKS_FILE"
    elif [ "$JQ_CMD" = "python3" ]; then
        python3 <<PYTHON
import json

with open("$TASKS_FILE", "r") as f:
    data = json.load(f)

data["tasks"] = [task for task in data["tasks"] if task["id"] != "$id"]

with open("$TASKS_FILE", "w") as f:
    json.dump(data, f, indent=2)
PYTHON
    else
        echo "Warning: jq or python3 not available. Task deletion may be limited."
        return 1
    fi
}

# Export tasks
export_tasks() {
    local output_file="${1:-tasks_export.json}"
    init_tasks
    cp "$TASKS_FILE" "$output_file"
    echo "Tasks exported to $output_file"
}

# Import tasks
import_tasks() {
    local input_file="${1:-tasks_export.json}"
    if [ -f "$input_file" ]; then
        cp "$input_file" "$TASKS_FILE"
        echo "Tasks imported from $input_file"
    else
        echo "Error: File $input_file not found"
        return 1
    fi
}

# Print tasks tree
print_tree() {
    init_tasks
    if [ "$HAS_PYTHON3" -eq 1 ]; then
        python3 <<PYTHON
import json
import sys

try:
    with open("$TASKS_FILE", "r") as f:
        data = json.load(f)
except Exception:
    print("No tasks found.")
    sys.exit(0)

tasks = data.get("tasks", [])
if not tasks:
    print("No tasks found.")
    sys.exit(0)

# Build parent map
children = {}
roots = []
tasks_map = {t["id"]: t for t in tasks}

for task in tasks:
    pid = task.get("parent")
    if pid and pid in tasks_map: # Valid parent
        if pid not in children:
            children[pid] = []
        children[pid].append(task)
    else:
        roots.append(task)

def print_node(task, prefix="", is_last=True):
    # Determine connector
    connector = "└── " if is_last else "├── "
    
    # Status icon
    status = task.get("status", "todo")
    if status == "done":
        icon = "\033[0;32m✓\033[0m" # Green check
    elif status == "in-progress":
        icon = "\033[0;33m●\033[0m" # Yellow dot
    elif status == "blocked":
        icon = "\033[0;31m✖\033[0m" # Red x
    else:
        icon = "○" # Empty circle
        
    print(f"{prefix}{connector}[{icon}] {task['description']} \033[0;90m({task['id']})\033[0m")
    
    child_prefix = prefix + ("    " if is_last else "│   ")
    kids = children.get(task["id"], [])
    for i, child in enumerate(kids):
        print_node(child, child_prefix, i == len(kids) - 1)

print("\033[1mTask Tree\033[0m")
for i, root in enumerate(roots):
    print_node(root, "", i == len(roots) - 1)
PYTHON
    else
        echo "Tree view requires python3."
    fi
}

# Print tasks report (markdown)
print_report() {
    init_tasks
    if [ "$HAS_PYTHON3" -eq 1 ]; then
        python3 <<PYTHON
import json
import sys

try:
    with open("$TASKS_FILE", "r") as f:
        data = json.load(f)
except Exception:
    print("No tasks found.")
    sys.exit(0)

tasks = data.get("tasks", [])
if not tasks:
    print("No tasks available for report.")
    sys.exit(0)

print("# Task Report")
print("")
print("| ID | Description | Status | Priority | Tags |")
print("|---|---|---|---|---|")

for task in tasks:
    tags = ", ".join(task.get("tags", []))
    print(f"| {task['id']} | {task['description']} | {task['status']} | {task['priority']} | {tags} |")
PYTHON
    else
        echo "Report generation requires python3."
    fi
}

# Main command handler
case "${1:-}" in
    create)
        shift
        create_task "$@"
        ;;
    update)
        shift
        update_task "$@"
        ;;
    list)
        shift
        list_tasks "$@"
        ;;
    get)
        shift
        get_task "$@"
        ;;
    delete)
        shift
        delete_task "$@"
        ;;
    export)
        shift
        export_tasks "$@"
        ;;
    import)
        shift
        import_tasks "$@"
        ;;
    comment)
        shift
        add_comment "$@"
        ;;
    tag)
        shift
        add_tag "$@"
        ;;
    search)
        shift
        search_tasks "$@"
        ;;
    tree)
        shift
        print_tree "$@"
        ;;
    report)
        shift
        print_report "$@"
        ;;
    *)
        echo "Usage: $0 {create|update|list|tree|report|get|delete|export|import|comment|tag|search} [args]"
        echo ""
        echo "Commands:"
        echo "  create <desc> [priority] [parent] [depends_on] [tags]  - Create a new task"
        echo "  update <id> <field> <value>                            - Update a task field"
        echo "  list [status] [priority] [tag] [desc]                 - List tasks (optionally filtered)"
        echo "  tree                                                   - Show tasks as tree"
        echo "  report                                                 - Generate Markdown status report"
        echo "  search [status] [priority] [tag] [desc]                - Search tasks"
        echo "  get <id>                                               - Get task by ID"
        echo "  delete <id>                                            - Delete a task"
        echo "  comment <id> <comment>                                  - Add comment to task"
        echo "  tag <id> <tag>                                         - Add tag to task"
        echo "  export [file]                                          - Export tasks to JSON"
        echo "  import [file]                                          - Import tasks from JSON"
        echo ""
        echo "Examples:"
        echo "  $0 create 'Implement feature' high '' 'frontend,urgent'"
        echo "  $0 tree"
        echo "  $0 report > report.md"
        exit 1
        ;;
esac
