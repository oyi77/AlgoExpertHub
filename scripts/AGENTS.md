<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Scripts

## Purpose

Shell utility scripts for deployment, server maintenance, and operational tasks. Includes log cleanup, permission fixes, disk monitoring, deployment automation, and documentation publishing.

## Key Files

| File | Description |
|------|-------------|
| `cleanup-laravel-logs.sh` | Truncate/clean old Laravel log files to free disk space |
| `fix-laravel-permissions.sh` | Fix file/directory ownership and permissions for Laravel |
| `monitor-disk-usage.sh` | Monitor and report disk usage across project directories |
| `deploy-github-wiki.sh` | Publish documentation to GitHub Wiki repository |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `deployment/` | Production and staging deployment scripts |

## For AI Agents

### Working In This Directory
- All scripts are executable bash scripts
- Review scripts before running — they modify server state (permissions, files, deployments)
- Deployment scripts in `deployment/` require a `deploy.config` file (see `deploy.config.example`)
- Scripts are designed for Linux servers (Ubuntu/Debian assumed)

### Common Patterns
- Scripts use `#!/bin/bash` with `set -e` for error handling
- Deployment scripts read configuration from `deploy.config` files
- Permission scripts target the `www-data` user for web server compatibility

### Deployment Scripts
- `deployment/deploy.sh` — Main production deployment script
- `deployment/deploy-1panel.sh` — Deployment for 1Panel hosting platform
- `deployment/deploy.config.example` — Template for deployment configuration
- `deployment/deploy.config.staging` — Staging environment config

## Dependencies

### Internal
- `../AGENTS.md` — Project-wide rules
- `../main/` — Laravel application that scripts operate on
- `../docs/` — Documentation published by `deploy-github-wiki.sh`

### External
- Bash shell
- Standard Unix utilities (find, chmod, chown, du, rsync)
- Git (for wiki deployment)
