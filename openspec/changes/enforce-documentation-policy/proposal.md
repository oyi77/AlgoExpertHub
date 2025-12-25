# Enforce Documentation Policy

## Summary
Enforce a strict policy where documentation must be updated whenever code is changed or features are added. Ensure documentation is the single source of truth, non-redundant, and automatically deployed to the GitHub Wiki.

## Motivation
- **Consistency**: The codebase and documentation often drift apart, leading to confusion.
- **Accuracy**: Users and developers rely on out-of-date information.
- **Efficiency**: Redundant documentation wastes maintenance effort.
- **Accessibility**: The GitHub Wiki should always reflect the latest state of the documentation.

## Proposed Solution
1.  **Rule Enforcement**: Implement a `.cursor/rules` file to mandate doc updates with code changes.
2.  **Deduplication**: Audit and remove redundant documentation.
3.  **Automated Deployment**: Establish a workflow to run `scripts/deploy-github-wiki.sh` for updating the GitHub Wiki.
