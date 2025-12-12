# /evolve Command

Update specifications as requirements or implementation evolve.

## Usage

/evolve [feature-name] [changes description]

## Purpose

Keep specifications aligned with implementation reality. When requirements change or implementation reveals new insights, update the specs accordingly.

## Prerequisites

**REQUIRED**:
At least one of:
- Requirements: `.kiro/specs/[feature-name]/requirements.md`
- Design: `.kiro/specs/[feature-name]/design.md`
- Tasks: `.kiro/specs/[feature-name]/tasks.md`

## Workflow

1. **Identify Changes**
   - Parse change description
   - Determine affected documents
   - Review current implementation

2. **Update Documents**
   - Update requirements if scope changed
   - Update design if architecture changed
   - Update tasks if work breakdown changed
   - Maintain change history in each document

3. **Propagate Changes**
   - Ensure consistency across all docs
   - Update dependent sections
   - Add to change history

4. **Update Task Tracking**
   - If using bd: Update task descriptions
   - If using Cursor: Update task content
   - Add new tasks if needed
   - Mark outdated tasks as cancelled

5. **Verify with User**
   - Show summary of changes
   - Ask for approval
   - Continue implementation if approved

## Output

Updates affected documents in `.kiro/specs/[feature-name]/` with:
- Modified sections
- Change history entry
- Updated timestamps

## Example

```
/evolve payment-gateway Add support for recurring subscriptions with webhook handling
```

This will:
1. Update requirements with new subscription user stories
2. Update design with recurring payment architecture
3. Update tasks with new implementation steps
4. Add change history to all documents
5. Update task tracking if already created

## Change History Format

Each updated document gets:

```markdown
## Change History

### 2025-12-12: Added Recurring Subscriptions
- Added Requirement 3: Recurring Payment Support
- Updated Component: PaymentService for subscription handling
- Added Tasks: 3.1-3.4 for webhook implementation
```

## Task Tracking Updates

### Using Beads (bd)

```bash
# Update existing task
bd update bd-123 --json

# Add new tasks
bd create "New task from evolution" -t task -p 1 --json

# Cancel outdated tasks
bd update bd-456 --status cancelled --json
```

### Using Cursor Tasks

```javascript
// Update existing
update_tasks([{
  id: "task_123",
  content: "Updated description"
}])

// Add new tasks
add_tasks([{
  id: "task_new",
  content: "New task",
  status: "PENDING"
}])

// Cancel outdated
update_tasks([{
  id: "task_456",
  status: "CANCELLED"
}])
```

## Integration Points

- Reads/updates `.kiro/specs/[feature-name]/` documents
- Follows patterns in `.kiro/steering/`
- Maintains consistency with codebase
- Updates task tracking system (bd or Cursor)

## When to Use

- Requirements changed during development
- Implementation reveals better approach
- User requests modifications
- Integration requirements discovered
- Performance issues require redesign
- Security considerations added
- Edge cases discovered during testing

## Best Practices

- Document all changes with rationale
- Maintain comprehensive change history
- Update all affected documents
- Verify consistency across specs
- Get user approval for major changes
- Update task tracking immediately
- Consider impact on existing code

## Verification Message

After evolution:

```
📝 I've updated the specs for [feature-name]:

✅ Changes made:
- [Change 1]
- [Change 2]

📋 Affected documents:
- requirements.md: [summary]
- design.md: [summary]
- tasks.md: [summary]

Please review the changes. Continue with implementation? (yes/no)
```

