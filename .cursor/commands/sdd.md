# /sdd Command

Generate complete specs (requirements, design, tasks) in one command.

## Usage

/sdd [feature-name] [description]

## Purpose

This is the PRIMARY command for Specs-Driven Development. With a simple prompt, it generates all necessary documentation before any code is written.

## Workflow

1. **Generate Requirements**
   - Analyze user description
   - Review existing codebase patterns in `.kiro/steering/`
   - Create comprehensive requirements with user stories
   - Define acceptance criteria in WHEN/THE/SHALL format
   - Identify edge cases and success metrics

2. **Generate Design**
   - Design architecture based on requirements
   - Define component structure
   - Specify database schema
   - Design API contracts
   - Plan security and performance strategy

3. **Generate Tasks**
   - Break down design into actionable tasks
   - Define task dependencies
   - Provide realistic estimates
   - Set clear acceptance criteria
   - Check for bd availability for task tracking

4. **Verify with User**
   - Present summary of generated specs
   - Ask user to review documents
   - Get explicit approval before proceeding
   - Allow for iteration if needed

## Output

Creates in `.kiro/specs/[feature-name]/`:
- `requirements.md` - What to build (user stories, acceptance criteria)
- `design.md` - How to build it (architecture, components, APIs)
- `tasks.md` - Steps to build it (task breakdown, estimates)

## Example

```
/sdd payment-gateway Add Stripe integration with webhook support for subscription payments
```

This will:
1. Generate requirements with user stories for payment flows
2. Design architecture including Stripe service, webhook handler, payment models
3. Create task breakdown with estimates
4. Ask: "Ready to build? (yes/no)"

## Verification Message

After generation, ALWAYS show:

```
📋 I've generated the specs for [feature-name]:

✅ Requirements: [brief summary]
✅ Design: [brief summary]  
✅ Tasks: [count] tasks identified

Please review the documents in `.kiro/specs/[feature-name]/`

Are the specs accurate and complete? Ready to build? (yes/no)
```

## Task Management

The command automatically:
- Checks if `bd` (beads) is available: `which bd`
- If available: Use beads for task tracking
- If not: Fallback to Cursor's task system

## Next Steps

After user verification:
- User says "yes" → Proceed with `/build [feature-name]`
- User has changes → Use `/evolve [feature-name] [changes]`
- User needs more info → Use `/analyze [topic]`

## Integration Points

- Reads project patterns from `.kiro/steering/`
- Follows Laravel architecture conventions
- Respects addon system patterns
- Maintains consistency with existing code

## Best Practices

1. **Be Comprehensive**: Cover all aspects (requirements, design, tasks)
2. **Be Clear**: Use simple, unambiguous language
3. **Be Specific**: Include concrete examples and code patterns
4. **Verify First**: NEVER build without user approval
5. **Use Context**: Reference existing patterns from steering files
