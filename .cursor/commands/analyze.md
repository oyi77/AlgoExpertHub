# /analyze Command

Analyze and explore the entire codebase to build a comprehensive and persistent set of cursor rules that codify technical and business knowledge for ongoing reference.

## Usage

/analyze

## Purpose

- Systematically inspect and understand the whole codebase, uncovering its:
  - Technical architecture and data flows
  - Major components, services, and dependencies
  - Business logic and domain flows
  - Key technical specifications, patterns, and extension points
- Codify this knowledge as reusable cursor rules, ensuring all essential learnings are persisted for future operations within the same codebase.

## Workflow

1. Recursively scan all code, configuration, and documentation files in the repository.
2. Extract meaningful information about the system’s structure, technical and business flows, as well as architectural decisions.
3. Summarize and embed findings as structured cursor rules.
4. Persist these rules for reuse on all subsequent prompts/commands within the codebase context.
5. On codebase changes (major merges, refactors, additions, deletions), automatically re-execute or prompt to update the analysis so the cursor rules always reflect the latest codebase state.

## Outputs

- `.cursor/rules/*.mdc` or equivalent storage artifact containing:
  - Technical architecture map
  - Service/component inventory
  - Business processes and major workflows
  - Integration points and external dependencies
  - Relevant technology-specific knowledge and conventions

## Best Practices

- Treat cursor rules as the primary memory for reasoning about the codebase. 
- Apply and reference these rules for all code generation, explanations, task breaking, and feature work within the codebase.
- Routinely refresh the rules after any significant codebase evolution or periodic intervals.

## Example

/analyze

## Notes

- Cursor rules enable continuity for AI-driven development: they should be leveraged on every new request to ensure context-rich, consistent, and accurate results.
