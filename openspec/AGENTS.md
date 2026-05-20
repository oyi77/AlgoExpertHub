<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# OpenSpec

## Purpose

OpenSpec specification system for managing feature proposals, change requests, and architectural decisions. Provides a structured format for planning new capabilities, breaking changes, and significant feature work before implementation begins.

## Key Files

| File | Description |
|------|-------------|
| `project.md` | Project-level specification — overall goals, constraints, and guidelines |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `changes/` | Change proposals for features, fixes, and architectural improvements |
| `specs/` | Detailed feature specifications |
| `changes/archive/` | Completed or rejected change proposals |

## For AI Agents

### Working In This Directory
- **Always consult this directory** when a request mentions planning, proposals, specs, or architectural changes
- Read `project.md` for project-level constraints and guidelines before proposing changes
- Existing change proposals in `changes/` provide context for ongoing work
- Feature specs in `specs/` define detailed requirements for implementation

### When to Use OpenSpec
- Introducing new capabilities or features
- Making breaking changes
- Architectural shifts or large refactors
- Performance or security improvements
- When the task is ambiguous and you need authoritative specifications before coding

### Common Patterns
- Change proposals follow a structured format defined in the OpenSpec system
- Proposals include: motivation, design, implementation plan, and migration strategy
- Active proposals live in `changes/`, completed ones move to `changes/archive/`
- The `openspec update` command refreshes the OpenSpec instruction block in `CLAUDE.md`

### Active Change Proposals
- `changes/enforce-documentation-policy` — Documentation enforcement
- `changes/fix-exchange-connection-flow` — Exchange connection fixes
- `changes/implement-backtesting-engine` — Backtesting engine
- `changes/improve-codebase-quality` — Code quality improvements
- `changes/optimize-trading-performance-and-ux` — Performance and UX optimization

## Dependencies

### Internal
- `../AGENTS.md` — Project-wide rules
- `../CLAUDE.md` — Contains OpenSpec instruction block that references this directory
- `../docs/` — Documentation that should align with specs
- `../main/` — Implementation target for specified changes

### External
- OpenSpec specification format
