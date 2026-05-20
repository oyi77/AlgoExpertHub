<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Docs

## Purpose

Project documentation covering architecture, API reference, core workflows, deployment guides, development onboarding, addon documentation, and user guides.

## Key Files

| File | Description |
|------|-------------|
| `README.md` | Documentation index with links to all doc sections |
| `RefactoringGuidelines.md` | Code refactoring conventions and best practices |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `architecture/` | System architecture docs — overview, addon system, data flow, technology stack, configuration, database/cache/queue config |
| `api/` | API documentation — reference, authentication, rate limiting, webhooks, signal processing, trading operations, user management |
| `core/` | Core workflow docs — end-to-end trading flow, signal ingestion, payment integration, trading execution, market-closed handling |
| `development/` | Developer guides — onboarding, addon development, theme development, database schema, performance optimization, troubleshooting |
| `deployment/` | Deployment guides — Docker setup, general installation guide, GitHub Wiki publishing |
| `addons/` | Per-addon documentation — trading management, AI connection, copy trading, filter strategy, page builder, OpenRouter integration |
| `images/` | Documentation images and diagrams |
| `migration/` | Migration guides |
| `archive/` | Archived documentation |
| `repowiki/` | Repository wiki content |
| `user-guides/` | End-user documentation |

## For AI Agents

### Working In This Directory
- Documentation is written in Markdown
- Refer to `README.md` for the full documentation index
- Architecture decisions and data flows are documented in `architecture/`
- When adding new features, update the relevant docs in the appropriate subdirectory
- API docs in `api/` should match the actual route definitions in `main/routes/`

### Common Patterns
- Docs reference code paths relative to `main/` (e.g., `app/Services/`)
- Architecture docs include Mermaid diagrams where applicable
- Addon docs follow a consistent structure: overview, user guide, developer guide

## Dependencies

### Internal
- `../AGENTS.md` — Project-wide rules
- `../main/` — Source code that documentation describes
- `../openspec/` — Feature specifications that docs should align with

### External
- Markdown (GitHub Flavored Markdown)
