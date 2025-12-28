# documentation-policy Specification

## Purpose
TBD - created by archiving change enforce-documentation-policy. Update Purpose after archive.
## Requirements
### Requirement: Update Docs on Code Change
Parameters and logic MUST be kept in sync with code changes.
#### Scenario: Code Modification
- **Given** a developer modifies a feature or business logic in the codebase
- **When** the changes are committed
- **Then** the corresponding documentation in `docs/` MUST be updated to reflect the new behavior.

### Requirement: New Feature Documentation
Every new feature MUST have accompanying documentation.
#### Scenario: New Feature
- **Given** a new feature is added
- **When** the feature is implemented
- **Then** new documentation MUST be created in `docs/`
- **And** it must not duplicate existing information (cross-reference instead).

### Requirement: Wiki Synchronization
Public wiki MUST mirror the repo docs.
#### Scenario: Wiki Deployment
- **Given** changes are made to `docs/`
- **When** the changes are finalized
- **Then** `scripts/deploy-github-wiki.sh` SHOULD be executed to update the GitHub Wiki.

### Requirement: Non-Redundant Documentation
Documentation MUST NOT be redundant; it MUST serve as a single source of truth.
#### Scenario: Redundancy Check
- **Given** a plan to write documentation
- **When** drafting the content
- **Then** the `docs/` directory MUST be searched to ensure the topic isn't already covered.
- **And** if it is covered, update the existing file instead of creating a new one.

