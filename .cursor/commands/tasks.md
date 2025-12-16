# /tasks Command

Generate task breakdown from design document.

## Usage

/tasks [feature-name]

## Purpose

Create actionable task breakdown with estimates, dependencies, and acceptance criteria based on the design document.

## Prerequisites

**REQUIRED**:
- Requirements document: `.kiro/specs/[feature-name]/requirements.md`
- Design document: `.kiro/specs/[feature-name]/design.md`

## Workflow

1. **Load Documents**
   - Read requirements and design documents
   - Understand architecture and components
   - Review project patterns from `.kiro/steering/`

2. **Break Down Work**
   - Identify implementation phases
   - Create small, manageable tasks (1-2 days max)
   - Define task dependencies
   - Provide realistic estimates
   - Set clear acceptance criteria

3. **Task Tracking Setup**
   - Check if `bd` (beads) is available
   - Include task tracking metadata
   - Plan for bd or Cursor tasks integration

4. **Output Document**
   - Save to `.kiro/specs/[feature-name]/tasks.md`
   - Follow standard tasks template

## Output

Creates `.kiro/specs/[feature-name]/tasks.md` with:

```markdown
# [Feature Name] Tasks

## Task Tracking

**System**: Beads (bd) | Cursor Tasks
**Feature**: [feature-name]
**Status**: Not Started | In Progress | Complete

## Task Breakdown

### Phase 1: [Phase Name]

#### Task 1.1: [Task Name]
**Description**: [What to do]
**Acceptance Criteria**:
- [ ] [Criterion 1]
- [ ] [Criterion 2]
**Estimate**: [X hours/days]
**Dependencies**: None | Task X.X
**Priority**: High | Medium | Low
**Status**: pending | in_progress | complete
**Tracking**: bd-123 | cursor-task-001

#### Task 1.2: [Task Name]
...

### Phase 2: [Phase Name]
...

## Summary

**Total Tasks**: [count]
**Total Estimate**: [X days]
**Critical Path**: [Task sequence]
```

## Example

```
/tasks api-rate-limiting
```

This will:
1. Read design from `.kiro/specs/api-rate-limiting/design.md`
2. Break down into phases:
   - Database schema
   - Middleware implementation
   - Testing
3. Create tasks with estimates and dependencies
4. Prepare for task tracking integration

## Task Tracking Integration

### Beads (bd) - Preferred

If bd is available, tasks will be created with:
```bash
bd create "Task: [name]" -t task -p 1 --json
```

Task metadata includes:
- Task ID reference
- Status tracking
- Priority levels

### Cursor Tasks - Fallback

If bd is not available, tasks use Cursor's system:
```javascript
add_tasks([{
  id: "task_001",
  content: "Task description",
  status: "PENDING"
}])
```

## Integration Points

- Reads from `.kiro/specs/[feature-name]/requirements.md`
- Reads from `.kiro/specs/[feature-name]/design.md`
- Follows patterns in `.kiro/steering/`
- Prepares for `/build` command execution

## Next Steps

After tasks are generated:
1. Review task breakdown
2. Verify estimates are realistic
3. Use `/build [feature-name]` to start implementation

## Best Practices

- Keep tasks small (1-2 days max)
- Define clear acceptance criteria
- Map dependencies explicitly
- Provide realistic estimates
- Group related tasks in phases
- Include testing tasks
- Plan for integration tasks

