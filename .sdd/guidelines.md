# Specs-Driven Development (SDD) Guidelines

Inspired by Kiro's streamlined approach to building features with clarity and confidence.

## 🎯 Core Philosophy

**"Simple prompt → Complete specs → User verification → Build"**

With a single command, generate comprehensive documentation that answers:
1. **WHAT** needs to be built (Requirements)
2. **HOW** it will be built (Design)
3. **STEPS** to build it (Tasks)

Then get user approval before writing any code.

## 📋 SDD Workflow

### Step 1: Generate Specs

```
/sdd [feature-name] [description]
```

This generates three documents in `.kiro/specs/[feature-name]/`:

#### requirements.md
- **Introduction**: Context and problem statement
- **Glossary**: Key terms and definitions
- **User Stories**: Who, what, why
- **Acceptance Criteria**: WHEN/THE/SHALL format
- **Edge Cases**: Boundary conditions
- **Success Metrics**: How to measure success

#### design.md
- **Architecture Overview**: High-level design
- **Component Design**: Detailed components
- **Database Schema**: Tables and relationships
- **API Contracts**: Endpoints and data structures
- **Security Considerations**: Auth, validation, encryption
- **Performance Strategy**: Caching, optimization
- **Error Handling**: Error scenarios and recovery
- **Technology Choices**: With justification

#### tasks.md
- **Task Breakdown**: Small, actionable items
- **Dependencies**: What depends on what
- **Estimates**: Realistic time estimates
- **Acceptance Criteria**: How to verify completion
- **Task Tracking**: Integration with bd/cursor tasks

### Step 2: Verify with User

**CRITICAL**: Always ask the user:

> "📋 I've generated the specs for [feature-name]:
>
> - ✅ Requirements: [summary]
> - ✅ Design: [summary]
> - ✅ Tasks: [count] tasks identified
>
> Please review the documents in `.kiro/specs/[feature-name]/`
>
> Are the specs accurate and complete? Ready to build? (yes/no)"

### Step 3: Build

Only after user approval:

```
/build [feature-name]
```

This:
1. Creates tasks in bd (if available) or Cursor tasks (fallback)
2. Implements features following the design
3. Updates task status as work progresses
4. Marks tasks complete when done

## 🔧 Task Management Integration

### Beads (bd) - Primary

If `bd` command is available:

```bash
# Check if bd is available
which bd

# Create tasks
bd create "Task title" -t task -p 1 --json

# Update status
bd update bd-42 --status in_progress --json

# Complete
bd close bd-42 --reason "Completed" --json
```

### Cursor Tasks - Fallback

If bd is not available, use Cursor's task system:
- Use `add_tasks` tool to create tasks
- Use `update_tasks` tool to update progress
- Tasks displayed in Cursor sidebar

### Auto-Detection

The system automatically detects which is available:

```javascript
if (beadsAvailable) {
  // Use bd commands
  exec('bd create ...')
} else {
  // Use Cursor tasks
  add_tasks([...])
}
```

## 📐 Documentation Standards

### Requirements Document

**Format**:
```markdown
# [Feature Name] Requirements

## Introduction
[Context and problem statement]

## Glossary
- **Term**: Definition

## Requirements

### Requirement 1: [Name]
**User Story:** As a [role], I want [feature], so that [benefit]

#### Acceptance Criteria
1. WHEN [condition], THE System SHALL [behavior]
2. WHEN [condition], THE System SHALL [behavior]
```

### Design Document

**Format**:
```markdown
# [Feature Name] Design

## Architecture Overview
[High-level design with diagrams if needed]

## Component Design

### Component 1: [Name]
**Purpose**: [What it does]
**Responsibilities**:
- [Responsibility 1]
- [Responsibility 2]

## Database Schema

### Table: [name]
```sql
CREATE TABLE ...
```

## API Contracts

### Endpoint: POST /api/...
**Request**:
```json
{ ... }
```
**Response**:
```json
{ ... }
```
```

### Tasks Document

**Format**:
```markdown
# [Feature Name] Tasks

## Task Breakdown

### Phase 1: [Phase Name]

#### Task 1.1: [Task Name]
**Description**: [What to do]
**Acceptance Criteria**:
- [ ] [Criterion 1]
- [ ] [Criterion 2]
**Estimate**: [hours/days]
**Dependencies**: None | Task 1.2
**Status**: pending | in_progress | complete
```

## ⚙️ Configuration

### Specs Location

All specs are stored in:
```
.kiro/specs/[feature-name]/
  ├── requirements.md
  ├── design.md
  └── tasks.md
```

### Legacy Support

For compatibility with existing SDD 2.0 docs:
```
specs/active/[feature-name]/
  ├── spec.md          → requirements.md
  ├── plan.md          → design.md
  └── tasks.md         → tasks.md
```

## 📚 Best Practices

### 1. Keep It Simple
- One command generates all specs
- Clear, concise language
- No unnecessary complexity

### 2. User Verification First
- ALWAYS verify specs before building
- Get explicit approval
- Allow for iteration

### 3. Task Granularity
- Tasks should be 1-2 days max
- Clear acceptance criteria
- Testable outcomes

### 4. Living Documents
- Update specs as requirements change
- Use `/evolve` command
- Keep version history

### 5. Integration Aware
- Check project patterns in `.kiro/steering/`
- Follow existing architecture
- Maintain consistency

## 🔄 Evolution & Updates

### Updating Specs

```
/evolve [feature-name] [changes description]
```

This:
1. Updates affected documents
2. Maintains change history
3. Re-verifies with user
4. Updates tasks if needed

### Version History

Each document tracks changes:
```markdown
## Change History
- 2025-12-12: Initial requirements
- 2025-12-13: Added payment flow
- 2025-12-14: Updated API design
```

## 🎯 Success Criteria

SDD is successful when:

1. ✅ **Clarity**: Anyone can understand what's being built
2. ✅ **Completeness**: All questions answered before coding
3. ✅ **Confidence**: User approves specs before implementation
4. ✅ **Traceability**: Every code change maps to a requirement
5. ✅ **Maintainability**: Specs serve as documentation

## 🛠️ Troubleshooting

### Beads not available
```bash
# Install beads
pip install beads-project

# Or system falls back to Cursor tasks automatically
```

### Specs incomplete
```
/evolve [feature-name] Add missing [aspect]
```

### Need more research
```
/analyze [topic]
```

## 📝 Quick Reference

| Command | Purpose | Output |
|---------|---------|--------|
| `/sdd` | Generate all specs | requirements.md, design.md, tasks.md |
| `/build` | Implement feature | Code + task tracking |
| `/evolve` | Update specs | Updated documents |
| `/analyze` | Research topic | Analysis document |
| `/requirements` | Just requirements | requirements.md |
| `/design` | Just design | design.md |
| `/tasks` | Just tasks | tasks.md |

---

**Remember**: Specs first, verification second, code last. 🚀

