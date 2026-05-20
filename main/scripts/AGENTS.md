<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Scripts

## Purpose
Development and maintenance shell scripts for code quality, documentation, and test scaffolding. Utility scripts for local development workflow -- not deployed to production.

## Key Files

| File | Purpose |
|------|---------|
| `lint.sh` | PHP syntax linting, strict types check, business-in-controller audit (flags `DB::` usage in controllers) |
| `generate-service-tests.sh` | Batch scaffold unit test files for 25+ services that lack tests. Generates boilerplate `TestCase` classes with `RefreshDatabase` |
| `docs-watch.sh` | File watcher using `fswatch` that auto-regenerates API docs (Scribe) when routes, controllers, or requests change |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| _(none)_ | All scripts are flat in this directory |

## For AI Agents

### Working In This Directory
- All scripts are executable bash scripts -- run from `main/` directory
- `lint.sh` is a quick code quality check: syntax errors, missing strict types, controller DB queries
- `generate-service-tests.sh` has a hardcoded path (`/opt/1panel/...`) -- update the `cd` path before using in other environments
- `docs-watch.sh` requires `fswatch` (macOS: `brew install fswatch`, Linux: `apt install fswatch`)
- These are developer tools, not CI/CD scripts -- CI config lives in `.github/`

### Common Patterns
- Scripts use `set -euo pipefail` for safe execution (except `lint.sh` which is simpler)
- All scripts output progress to stdout
- Scripts target the `main/` directory as working directory

## Dependencies

### Internal
- `app/` -- Linted and tested by these scripts
- `routes/` -- Watched by docs generator
- `tests/Unit/Services/` -- Target directory for generated test scaffolds

### External
- `php` -- PHP binary for syntax checking
- `fswatch` -- File watcher for docs-watch
- `composer` -- Used by docs-watch for `composer docs:generate`
