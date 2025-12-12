# Cursor Commands - Specs Driven Development

This directory contains custom Cursor IDE commands for Specs-Driven Development (SDD), inspired by Kiro's streamlined workflow.

## 🎯 SDD Philosophy

With a simple prompt, generate:
1. **Requirements** - What needs to be built
2. **Design** - How it will be built
3. **Tasks** - Step-by-step implementation plan

Then verify with user before building.

## 📋 Available Commands

### Primary Workflow
- **`/sdd [feature-name] [description]`** - Generate complete requirements, design, and tasks in one go
- **`/build [feature-name]`** - Implement feature after verification

### Individual Steps (for granular control)
- **`/requirements [feature-name] [description]`** - Generate requirements document
- **`/design [feature-name]`** - Generate technical design from requirements
- **`/tasks [feature-name]`** - Generate task breakdown from design
- **`/implement [feature-name]`** - Execute implementation

### Management Commands
- **`/evolve [feature-name] [changes]`** - Update specs as development progresses
- **`/analyze [feature-name]`** - Analyze existing feature or codebase

## 🚀 Quick Start

**Most Common Usage:**
```
/sdd payment-integration Add Stripe payment gateway with webhook support
```

This will:
1. Generate `requirements.md` with user stories and acceptance criteria
2. Generate `design.md` with architecture and implementation plan
3. Generate `tasks.md` with actionable items
4. Ask for your verification before proceeding

After you verify:
```
/build payment-integration
```

## 📊 Task Management

The system automatically uses:
- **Beads (bd)** - If available (preferred)
- **Cursor Tasks** - Fallback if beads not installed

## 📁 Output Location

All specs are stored in `.kiro/specs/[feature-name]/`:
- `requirements.md` - What to build
- `design.md` - How to build it
- `tasks.md` - Step-by-step plan

## 📖 Documentation

See `.sdd/guidelines.md` for detailed guidelines and best practices.

