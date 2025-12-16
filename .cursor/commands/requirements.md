# /requirements Command

Generate only the requirements document.

## Usage

/requirements [feature-name] [description]

## Purpose

Create a comprehensive requirements document without full design and tasks. Useful when you want to clarify requirements before proceeding to design phase.

## Workflow

1. **Analyze Request**
   - Parse feature description
   - Review existing codebase patterns in `.kiro/steering/`
   - Identify stakeholders and user roles

2. **Generate Requirements**
   - Write introduction and problem statement
   - Define glossary of terms
   - Create user stories (As a [role], I want [feature], so that [benefit])
   - Define acceptance criteria (WHEN/THE/SHALL format)
   - Identify edge cases
   - Define success metrics

3. **Output Document**
   - Save to `.kiro/specs/[feature-name]/requirements.md`
   - Follow standard requirements template

## Output

Creates `.kiro/specs/[feature-name]/requirements.md` with:

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

### Requirement 2: [Name]
...

## Edge Cases
1. [Edge case description]

## Success Metrics
- [Metric 1]: [Target]
- [Metric 2]: [Target]
```

## Example

```
/requirements api-rate-limiting Implement rate limiting for API endpoints to prevent abuse
```

## Next Steps

After requirements are generated:
1. Review and iterate if needed
2. Use `/design [feature-name]` to create design document
3. Or use `/sdd [feature-name]` to generate all specs at once

## Best Practices

- Use clear, unambiguous language
- Include all stakeholder perspectives
- Define measurable acceptance criteria
- Consider edge cases and error scenarios
- Reference existing patterns from `.kiro/steering/`
