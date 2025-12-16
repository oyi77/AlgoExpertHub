# SDD Migration - Kiro-Style Workflow

This document explains the migration from the old SDD 2.0 workflow to the new Kiro-inspired Specs-Driven Development workflow.

## 🎯 What Changed

### Philosophy Shift

**Before (SDD 2.0)**:
- Multiple workflow modes (Quick/Full)
- Many separate commands
- Focus on documentation types

**After (Kiro-Style)**:
- Single streamlined workflow
- Simple prompt → specs → verify → build
- Focus on user verification and task tracking

## 📋 Command Changes

### Primary Commands (NEW)

| Command | Purpose | Replaces |
|---------|---------|----------|
| `/sdd [feature] [desc]` | Generate all specs | `/brief`, `/specify`, `/plan` |
| `/build [feature]` | Implement after verification | `/implement` |

### Individual Commands (Optional)

| Command | Purpose | Status |
|---------|---------|--------|
| `/requirements [feature] [desc]` | Generate requirements only | NEW |
| `/design [feature]` | Generate design only | NEW (replaces `/plan`) |
| `/tasks [feature]` | Generate tasks only | UPDATED |
| `/evolve [feature] [changes]` | Update specs | UPDATED |
| `/analyze [feature]` | Analyze code/feature | KEPT |

### Deprecated Commands

| Command | Status | Use Instead |
|---------|--------|-------------|
| `/brief` | REMOVED | `/sdd` |
| `/specify` | REMOVED | `/sdd` or `/requirements` |
| `/plan` | REMOVED | `/sdd` or `/design` |
| `/research` | REMOVED | `/analyze` |
| `/upgrade` | REMOVED | N/A |
| `/implement` | DEPRECATED | `/build` |

## 📁 File Structure Changes

### Specs Location

**Before**:
```
specs/active/[feature-name]/
  ├── feature-brief.md
  ├── research.md
  ├── spec.md
  ├── plan.md
  └── tasks.md
```

**After**:
```
.kiro/specs/[feature-name]/
  ├── requirements.md  (was: spec.md)
  ├── design.md        (was: plan.md)
  └── tasks.md         (same)
```

### Document Mapping

| Old | New | Notes |
|-----|-----|-------|
| `feature-brief.md` | `requirements.md` | Simplified, focused on user stories |
| `spec.md` | `requirements.md` | Requirements-focused |
| `plan.md` | `design.md` | Technical design |
| `tasks.md` | `tasks.md` | Enhanced with tracking |
| `research.md` | N/A | Use `/analyze` instead |

## 🔧 Task Management Integration

### NEW: Beads (bd) Support

The system now auto-detects and prefers `bd` (beads) for task tracking:

```bash
# Check if bd is available
which bd

# Create task
bd create "Task title" -t task -p 1 --json

# Update status
bd update bd-123 --status in_progress --json

# Close task
bd close bd-123 --reason "Completed" --json
```

### Fallback: Cursor Tasks

If `bd` is not available, automatically falls back to Cursor's task system:

```javascript
add_tasks([{ id, content, status: "PENDING" }])
update_tasks([{ id, status: "IN_PROGRESS" }])
```

## 🚀 New Workflow

### Step 1: Generate Specs

```
/sdd payment-integration Add Stripe payment gateway with webhook support
```

This generates:
- `requirements.md` - User stories, acceptance criteria
- `design.md` - Architecture, components, APIs
- `tasks.md` - Task breakdown, estimates

### Step 2: Verify

**CRITICAL CHANGE**: System now ALWAYS asks for verification:

```
📋 I've generated the specs for payment-integration:

✅ Requirements: 3 user stories, 12 acceptance criteria
✅ Design: Service layer, webhook handler, 2 new tables
✅ Tasks: 8 tasks, ~3 days estimate

Please review `.kiro/specs/payment-integration/`

Ready to build? (yes/no)
```

### Step 3: Build

Only after user says "yes":

```
/build payment-integration
```

This:
1. Detects task system (bd or Cursor)
2. Creates tasks
3. Implements features
4. Updates task status
5. Verifies acceptance criteria

## 📖 Integration Points

### Project Patterns

Specs now automatically reference patterns from `.kiro/steering/`:
- `README.md` - Platform overview
- `laravel-architecture.md` - Laravel conventions
- `business-domain.md` - Business logic
- `addon-system.md` - Addon patterns
- `database-models.md` - Database schemas

### Auto-Detection

The system automatically:
- Checks for `bd` availability
- Reads project patterns
- Follows Laravel conventions
- Respects addon architecture

## 🔄 Migration Steps

### For Existing Features

If you have features in `specs/active/`:

1. **Option 1: Leave as-is** - Old specs still work
2. **Option 2: Migrate** - Copy to `.kiro/specs/` and rename:
   ```bash
   cp specs/active/my-feature/spec.md .kiro/specs/my-feature/requirements.md
   cp specs/active/my-feature/plan.md .kiro/specs/my-feature/design.md
   cp specs/active/my-feature/tasks.md .kiro/specs/my-feature/tasks.md
   ```

### For New Features

Always use the new workflow:
```
/sdd [feature-name] [description]
```

## 🎓 Learning the New System

### Quick Start

1. **Generate specs**: `/sdd my-feature Add feature X with Y support`
2. **Review docs**: Check `.kiro/specs/my-feature/`
3. **Verify**: Respond "yes" when asked
4. **Build**: System automatically implements with task tracking

### Key Differences

| Aspect | Old | New |
|--------|-----|-----|
| Commands | Multiple (`/brief`, `/specify`, etc.) | Single (`/sdd`) |
| Location | `specs/active/` | `.kiro/specs/` |
| Verification | Optional | MANDATORY |
| Task tracking | Manual | Auto (bd or Cursor) |
| Patterns | Manual reference | Auto-read from `.kiro/steering/` |

## 📚 Documentation

- **Commands**: See `.cursor/commands/` for detailed command docs
- **Guidelines**: See `.sdd/guidelines.md` for workflow guidelines
- **Rules**: See `.cursor/rules/sdd-toolkit.mdc` for AI rules

## 🆘 Troubleshooting

### Beads not working?

System automatically falls back to Cursor tasks. To use beads:
```bash
pip install beads-project
```

### Old commands not working?

Use new equivalents:
- `/brief` → `/sdd`
- `/specify` → `/requirements` or `/sdd`
- `/plan` → `/design` or `/sdd`
- `/implement` → `/build`

### Specs in wrong location?

New specs go to `.kiro/specs/[feature-name]/`
Old location `specs/active/` still supported but not preferred

## ✅ Benefits

1. **Simpler**: One command instead of many
2. **Safer**: Mandatory verification before building
3. **Smarter**: Auto-detects task system
4. **Integrated**: Auto-reads project patterns
5. **Consistent**: Follows Kiro's proven workflow

---

**Migration Date**: 2024-12-12
**Version**: 1.0
**Status**: Active
