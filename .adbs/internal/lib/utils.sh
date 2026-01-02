#!/bin/bash
# Common utilities for ADbS

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Print functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check for JSON processor (jq or python3)
check_json_processor() {
    if command -v jq &> /dev/null; then
        echo "jq"
        return 0
    elif command -v python3 &> /dev/null; then
        echo "python3"
        return 0
    else
        return 1
    fi
}

# Check dependencies
check_dependencies() {
    local missing=()
    for cmd in "$@"; do
        if ! command -v "$cmd" &> /dev/null; then
            missing+=("$cmd")
        fi
    done

    if [ ${#missing[@]} -gt 0 ]; then
        echo "Missing dependencies: ${missing[*]}"
        return 1
    fi
    return 0
}

# Ensure directory exists
ensure_dir() {
    local dir="$1"
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
    fi
}

# JSON helpers
json_get_key() {
    local file="$1"
    local key="$2"
    local processor=$(check_json_processor)
    
    if [ "$processor" = "jq" ]; then
        jq -r ".$key" "$file" 2>/dev/null
    elif [ "$processor" = "python3" ]; then
        python3 -c "import json; data=json.load(open('$file')); print(data.get('$key', ''))" 2>/dev/null
    else
        grep "\"$key\"" "$file" | cut -d':' -f2 | tr -d '," '
    fi
}
