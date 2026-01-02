# Documentation Consolidation - Design

## Architecture Overview

This is a file organization and path reference update task. No code architecture changes, only:
1. File system reorganization
2. Path reference updates in code
3. Link updates in documentation

## Component Design

### Component 1: File Inventory & Analysis
**Purpose**: Understand current state before making changes

**Responsibilities**:
- List all files in `main/docs/`
- List all files in `docs/`
- Identify duplicates by name
- Compare file contents for true duplicates
- Map file categories

**Implementation**:
```bash
# Inventory files
find main/docs -type f -name "*.md" > main_docs_files.txt
find docs -type f -name "*.md" > docs_files.txt

# Compare for duplicates
# Manual review of file names and contents
```

### Component 2: File Migration Strategy
**Purpose**: Move files from `main/docs/` to `docs/` with proper organization

**Responsibilities**:
- Determine target location for each file
- Handle naming conflicts
- Preserve directory structure where appropriate
- Create new directories if needed

**File Mapping**:
```
main/docs/CODING_STANDARDS.md
  → docs/development/coding-standards.md

main/docs/deployment-guide.md
  → docs/deployment/ (check if duplicate exists)

main/docs/migration/laravel-notify-migration.md
  → docs/migration/laravel-notify-migration.md

main/docs/performance-playbook.md
  → docs/development/performance-playbook.md
  (Note: docs/development/performance-optimization.md exists - check if duplicate)

main/docs/security-practices.md
  → docs/development/security-practices.md

main/docs/testing-strategy.md
  → docs/development/testing-strategy.md

main/docs/livewire-components.md
  → docs/development/livewire-components.md

main/docs/package-implementation-status.md
  → docs/development/package-implementation-status.md

main/docs/implementation-summary.md
  → docs/development/implementation-summary.md

main/docs/language-management-improvements.md
  → docs/development/language-management-improvements.md

main/docs/env-configuration-translation.md
  → docs/development/env-configuration-translation.md

main/docs/laravel-notify-migration-audit.md
  → docs/migration/laravel-notify-migration-audit.md

main/docs/laravel-notify-migration-summary.md
  → docs/migration/laravel-notify-migration-summary.md
```

### Component 3: Code Reference Updates
**Purpose**: Update all code that references documentation paths

**Files to Update**:
1. `main/app/Http/Controllers/DocumentationController.php`
   - Check `$this->docsPath = base_path('../docs');`
   - Verify it works with root `/docs`

2. Search for references:
   ```bash
   grep -r "main/docs" main/
   grep -r "../docs" main/
   grep -r "docs/" --include="*.md" --include="*.php"
   ```

3. Update README files that reference `main/docs`

### Component 4: Link Verification
**Purpose**: Ensure all internal documentation links work

**Responsibilities**:
- Check markdown files for internal links
- Verify relative paths are correct
- Update links that reference `main/docs/`
- Test DocumentationController routes

**Link Patterns to Fix**:
- `[text](../main/docs/file.md)` → `[text](../docs/file.md)`
- `[text](main/docs/file.md)` → `[text](docs/file.md)`
- Relative paths within docs

## Database Schema

N/A - No database changes

## API Contracts

N/A - No API changes

## File System Structure

### Before:
```
/
├── docs/
│   ├── README.md
│   ├── development/
│   ├── deployment/
│   └── ...
└── main/
    └── docs/
        ├── CODING_STANDARDS.md
        ├── deployment-guide.md
        └── ...
```

### After:
```
/
├── docs/
│   ├── README.md
│   ├── development/
│   │   ├── coding-standards.md
│   │   ├── performance-playbook.md
│   │   ├── security-practices.md
│   │   ├── testing-strategy.md
│   │   └── ...
│   ├── deployment/
│   │   └── (consolidated deployment guides)
│   ├── migration/
│   │   └── (all migration docs)
│   └── ...
└── main/
    └── (docs/ removed or empty)
```

## Implementation Steps

### Step 1: Backup
```bash
# Create backup
cp -r main/docs main/docs.backup
cp -r docs docs.backup
```

### Step 2: Create Target Directories
```bash
# Ensure target directories exist
mkdir -p docs/development
mkdir -p docs/migration
```

### Step 3: Move Files
```bash
# Move files one by one with proper naming
mv main/docs/CODING_STANDARDS.md docs/development/coding-standards.md
mv main/docs/performance-playbook.md docs/development/performance-playbook.md
# ... continue for all files
```

### Step 4: Handle Duplicates
```bash
# Compare files if duplicates exist
diff docs/deployment/general-guide.md main/docs/deployment-guide.md
# If different, merge or rename
# If same, remove duplicate
```

### Step 5: Update Code References
```php
// DocumentationController.php
// Verify: $this->docsPath = base_path('../docs');
// This should already point to root /docs, verify it works
```

### Step 6: Update Documentation Links
```bash
# Find and replace links in markdown files
find docs -name "*.md" -exec sed -i 's|main/docs/|docs/|g' {} \;
find docs -name "*.md" -exec sed -i 's|../main/docs/|../docs/|g' {} \;
```

### Step 7: Update README
```markdown
# Update docs/README.md to reflect new structure
# Add moved files to appropriate sections
```

### Step 8: Cleanup
```bash
# Remove empty main/docs directory
rm -rf main/docs
# Or keep empty directory if needed for structure
```

### Step 9: Verification
```bash
# Test DocumentationController
# Check all links work
# Verify no broken references
```

## Error Handling

- **File conflicts**: Compare contents, merge if different, keep best version
- **Missing directories**: Create as needed
- **Broken links**: Fix during link verification step
- **Code references**: Search systematically, update all occurrences

## Testing Strategy

1. **File existence**: Verify all files moved successfully
2. **Link testing**: Check internal documentation links
3. **Controller testing**: Test DocumentationController routes
4. **Search testing**: Verify documentation search works
5. **Navigation testing**: Test documentation navigation in admin panel

## Rollback Plan

If issues arise:
1. Restore from backup: `cp -r main/docs.backup main/docs`
2. Restore docs: `cp -r docs.backup/* docs/`
3. Revert code changes if any made
