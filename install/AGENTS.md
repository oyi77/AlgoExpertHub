<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-05-21 -->

# Install

## Purpose

Web-based installation wizard for initial platform setup. Handles database configuration, environment file generation, permission checks, admin user creation, and initial database seeding through a step-by-step PHP interface.

## Key Files

| File | Description |
|------|-------------|
| `index.php` | Installation wizard entry point and step router |
| `database.php` | Database connection configuration step |
| `login.php` | Admin account creation step |
| `permission.php` | File permission verification step |
| `extension.php` | PHP extension check step |
| `finish.php` | Installation completion and cleanup step |
| `seed-database.php` | Database seeding script |
| `seed-db.php` | Alternative database seeding script |

## Subdirectories

| Directory | Purpose |
|-----------|---------|
| `lib/` | Installation helper libraries and shared functions |
| `src/` | Installation source classes |

## For AI Agents

### Working In This Directory
- This is a standalone PHP application, not part of the Laravel framework
- Runs once during initial deployment to configure the platform
- The wizard generates `.env` in `main/` and runs migrations/seeders
- After installation, this directory can be deleted or locked down

### Common Patterns
- Each PHP file corresponds to one installation step
- Steps are sequential: check extensions -> configure DB -> set permissions -> create admin -> seed -> finish
- Uses raw PHP (no Laravel framework dependencies during install)

## Dependencies

### Internal
- `../AGENTS.md` — Project-wide rules
- `../main/.env.example` — Template for environment file generation
- `../main/database/seeders/` — Seeders invoked during installation

### External
- PHP 8.1+ runtime (web server)
- MySQL database (must be accessible for DB setup step)
