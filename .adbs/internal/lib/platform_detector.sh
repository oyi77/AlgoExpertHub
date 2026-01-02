#!/bin/bash
# Platform/IDE detector - detects which IDE/platform is being used

detect_platform() {
    local platforms=()
    
    # Check for Cursor
    if [ -d ".cursor" ] || [ -n "${CURSOR}" ]; then
        platforms+=("cursor")
    fi
    
    # Check for Trae
    if [ -d ".trae" ] || [ -n "${TRAE}" ]; then
        platforms+=("trae")
    fi
    
    # Check for Gemini
    if [ -d ".gemini" ] || [ -n "${GEMINI}" ]; then
        platforms+=("gemini")
    fi
    
    # Check for VS Code
    if [ -d ".vscode" ] || [ -n "${VSCODE}" ] || [ -n "${CODE}" ]; then
        platforms+=("vscode")
    fi
    
    # Check for JetBrains IDEs
    if [ -d ".idea" ]; then
        platforms+=("jetbrains")
    fi
    
    # Check for Vim/Neovim
    if [ -f ".vimrc" ] || [ -d ".config/nvim" ]; then
        platforms+=("vim")
    fi
    
    # Check for Emacs
    if [ -f ".emacs" ] || [ -f ".emacs.d" ]; then
        platforms+=("emacs")
    fi

    # Check for Zed
    if [ -d ".zed" ] || [ -n "${ZED_TERM}" ]; then
        platforms+=("zed")
    fi

    # Check for Sublime Text
    if [ -n "$(find . -maxdepth 1 -name "*.sublime-project" -print -quit)" ]; then
        platforms+=("sublime")
    fi

    # Check for Helix
    if [ -d ".helix" ]; then
        platforms+=("helix")
    fi
    
    # If multiple platforms detected, return all (comma-separated)
    # If single platform, return it
    # If none, return generic
    if [ ${#platforms[@]} -eq 0 ]; then
        echo "generic"
    elif [ ${#platforms[@]} -eq 1 ]; then
        echo "${platforms[0]}"
    else
        # Return comma-separated list
        IFS=','; echo "${platforms[*]}"
    fi
    
    return 0
}

# Detect all platforms (returns array)
detect_all_platforms() {
    local platforms=()
    
    [ -d ".cursor" ] || [ -n "${CURSOR}" ] && platforms+=("cursor")
    [ -d ".trae" ] || [ -n "${TRAE}" ] && platforms+=("trae")
    [ -d ".gemini" ] || [ -n "${GEMINI}" ] && platforms+=("gemini")
    [ -d ".vscode" ] || [ -n "${VSCODE}" ] || [ -n "${CODE}" ] && platforms+=("vscode")
    [ -d ".idea" ] && platforms+=("jetbrains")
    [ -f ".vimrc" ] || [ -d ".config/nvim" ] && platforms+=("vim")
    [ -f ".emacs" ] || [ -f ".emacs.d" ] && platforms+=("emacs")
    [ -d ".zed" ] || [ -n "${ZED_TERM}" ] && platforms+=("zed")
    [ -n "$(find . -maxdepth 1 -name "*.sublime-project" -print -quit)" ] && platforms+=("sublime")
    [ -d ".helix" ] && platforms+=("helix")
    
    [ ${#platforms[@]} -eq 0 ] && platforms+=("generic")
    
    printf '%s\n' "${platforms[@]}"
}

get_rules_dir() {
    local platform="$1"
    [ -z "$platform" ] && platform=$(detect_platform | cut -d',' -f1)
    
    case "$platform" in
        cursor)
            echo ".cursor"
            ;;
        trae)
            echo ".trae"
            ;;
        gemini)
            echo ".gemini"
            ;;
        vscode)
            echo ".vscode"
            ;;
        jetbrains)
            echo ".idea"
            ;;
        zed)
            echo "."
            ;;
        helix)
            echo ".helix"
            ;;
        sublime)
            echo ".sublime"
            ;;
        windsurf)
            echo ".windsurf"
            ;;
        vim|emacs)
            echo ".ai-rules"
            ;;
        generic)
            echo "."
            ;;
        *)
            echo "."
            ;;
    esac
}

# Get rules directories for all detected platforms
get_all_rules_dirs() {
    detect_all_platforms | while IFS= read -r platform; do
        get_rules_dir "$platform"
    done
}

get_rules_file() {
    echo "rules"
}

# Main execution
case "${1:-}" in
    detect)
        detect_platform
        ;;
    detect-all)
        detect_all_platforms
        ;;
    rules-dir)
        get_rules_dir "${2:-}"
        ;;
    rules-dirs)
        get_all_rules_dirs
        ;;
    rules-file)
        get_rules_file
        ;;
    *)
        echo "Usage: $0 {detect|detect-all|rules-dir|rules-dirs|rules-file}"
        echo ""
        echo "Commands:"
        echo "  detect        - Detect primary platform/IDE"
        echo "  detect-all    - Detect all platforms/IDEs"
        echo "  rules-dir     - Get rules directory for platform (or primary)"
        echo "  rules-dirs    - Get rules directories for all platforms"
        echo "  rules-file    - Get rules filename"
        exit 1
        ;;
esac

