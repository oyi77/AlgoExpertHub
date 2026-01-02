# Documentation Consolidation - Requirements

## Introduction

Currently, documentation is scattered across two locations:
- `/docs` (root level) - Contains most documentation
- `/main/docs` - Contains additional documentation files

This creates confusion, duplication, and maintenance overhead. All documentation should be consolidated into a single location: `/docs` at the root level.

## Glossary

- **Root `/docs`**: Documentation directory at project root (`/opt/1panel/.../index/docs/`)
- **Main `/main/docs`**: Documentation directory inside main application (`/opt/1panel/.../index/main/docs/`)
- **DocumentationController**: Laravel controller that serves documentation via web interface

## Requirements

### Requirement 1: Consolidate Documentation Location
**User Story:** As a developer, I want all documentation in one location (`/docs`), so that I can easily find and maintain documentation.

#### Acceptance Criteria
1. WHEN consolidating documentation, THE system SHALL move all files from `main/docs/` to `docs/`
2. WHEN files are moved, THE system SHALL preserve directory structure where appropriate
3. WHEN files are moved, THE system SHALL avoid overwriting existing files in `docs/`
4. WHEN duplicate files exist, THE system SHALL merge or rename appropriately
5. WHEN consolidation is complete, THE `main/docs/` directory SHALL be empty or removed

### Requirement 2: Update Code References
**User Story:** As a developer, I want code references to documentation paths updated, so that documentation links work correctly.

#### Acceptance Criteria
1. WHEN code references `main/docs`, THE system SHALL update to `docs/`
2. WHEN DocumentationController references paths, THE system SHALL update to use root `/docs`
3. WHEN README files reference documentation paths, THE system SHALL update links
4. WHEN scripts reference documentation paths, THE system SHALL update paths

### Requirement 3: Organize Documentation Structure
**User Story:** As a developer, I want well-organized documentation structure, so that I can navigate documentation efficiently.

#### Acceptance Criteria
1. WHEN consolidating, THE system SHALL organize files into logical categories
2. WHEN similar files exist in both locations, THE system SHALL merge or organize appropriately
3. WHEN consolidation is complete, THE `docs/` directory SHALL have a clear structure
4. WHEN consolidation is complete, THE `docs/README.md` SHALL be updated with new structure

### Requirement 4: Verify No Broken Links
**User Story:** As a developer, I want all documentation links to work, so that I can access documentation without errors.

#### Acceptance Criteria
1. WHEN consolidation is complete, THE system SHALL verify all internal documentation links work
2. WHEN consolidation is complete, THE system SHALL verify DocumentationController can access all files
3. WHEN consolidation is complete, THE system SHALL verify no 404 errors in documentation navigation

## Out of Scope

- Rewriting documentation content
- Changing documentation format
- Updating documentation content (only structure/location)
- Modifying external documentation references (GitHub Wiki, etc.)

## Success Criteria

1. ✅ All files from `main/docs/` moved to `docs/`
2. ✅ All code references updated to use root `/docs`
3. ✅ Documentation structure is organized and logical
4. ✅ No broken links or 404 errors
5. ✅ `main/docs/` directory removed or empty
6. ✅ DocumentationController works with new structure
