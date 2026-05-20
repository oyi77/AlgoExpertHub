<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Docker

## Purpose

Docker configuration for containerized deployment of the AlgoExpertHub platform. Provides nginx web server config, MySQL database config, application entrypoint scripts, and supervisord process management.

## Key Files

| File | Description |
|------|-------------|
| `entrypoint.sh` | Container entrypoint script — handles initialization, migrations, permissions |
| `supervisord.conf` | Supervisord config — manages Laravel queue workers, Horizon, Octane |
| `.env.docker` | Docker-specific environment variables template |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `nginx/` | Nginx web server configuration (`nginx.conf`, `conf.d/` virtual host configs) |
| `mysql/` | MySQL database configuration (`my.cnf`) |

## For AI Agents

### Working In This Directory
- Docker Compose is defined at the project root (`../docker-compose.yml`), not in this directory
- The root `Dockerfile` builds the application image using files from this directory
- Modify `entrypoint.sh` for container initialization logic (migrations, seeding, permissions)
- Modify `supervisord.conf` to adjust process management (workers, Horizon, Octane)
- Nginx configs in `nginx/` control web server behavior and PHP-FPM proxying

### Common Patterns
- Environment variables are injected via `.env.docker` at build time
- Supervisord manages multiple long-running processes in a single container
- Nginx serves as the reverse proxy to PHP-FPM

## Dependencies

### Internal
- `../AGENTS.md` — Project-wide rules
- `../Dockerfile` — Root Dockerfile that references this directory
- `../docker-compose.yml` — Docker Compose orchestration
- `../main/` — The Laravel application being containerized

### External
- Docker / Docker Compose
- Nginx
- MySQL 5.7+
- Supervisord
- PHP-FPM
