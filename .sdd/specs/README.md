# Specifications Directory

This directory contains all feature specifications organized by status.

## Structure

- **active/** - Features currently in development or planning
- **archived/** - Completed or cancelled features

## Feature Directory Structure

Each feature in `active/` should have its own directory:

```
specs/active/[feature-name]/
├── feature-brief.md    # Quick 30-minute brief (if using quick mode)
├── research.md          # Research document (if needed)
├── spec.md              # Full specification
├── plan.md              # Technical architecture plan
├── tasks.md             # Task breakdown
└── progress.md          # Progress tracking (optional)
```

## Usage

1. Start with `/brief` for quick features (80% of cases)
2. Use `/upgrade` to convert to full SDD when needed
3. Use full SDD workflow for complex features (20% of cases)
4. Move completed features to `archived/` when done

