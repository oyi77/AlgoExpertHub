# /design Command

Generate technical design document from requirements.

## Usage

/design [feature-name]

## Purpose

Create detailed technical design based on existing requirements document. Defines architecture, components, database schema, APIs, and implementation strategy.

## Prerequisites

**REQUIRED**:
- Requirements document exists: `.kiro/specs/[feature-name]/requirements.md`

## Workflow

1. **Load Requirements**
   - Read requirements document
   - Understand user stories and acceptance criteria
   - Review project patterns from `.kiro/steering/`

2. **Design Architecture**
   - High-level architecture overview
   - Component breakdown
   - Integration points
   - Data flow diagrams

3. **Detailed Design**
   - Component responsibilities
   - Database schema (tables, relationships, indexes)
   - API contracts (endpoints, request/response)
   - Service layer design
   - Security considerations
   - Performance strategy
   - Error handling approach

4. **Technology Choices**
   - Select appropriate technologies
   - Justify decisions
   - Consider existing stack

5. **Output Document**
   - Save to `.kiro/specs/[feature-name]/design.md`
   - Follow standard design template

## Output

Creates `.kiro/specs/[feature-name]/design.md` with:

```markdown
# [Feature Name] Design

## Architecture Overview
[High-level design, diagrams, component interaction]

## Component Design

### Component 1: [Name]
**Purpose**: [What it does]
**Responsibilities**:
- [Responsibility 1]
- [Responsibility 2]

**Interfaces**:
- [Method signatures or API contracts]

## Database Schema

### Table: [table_name]
```sql
CREATE TABLE table_name (
    id BIGINT UNSIGNED PRIMARY KEY,
    field1 VARCHAR(255),
    created_at TIMESTAMP,
    INDEX idx_field1 (field1)
);
```

## API Contracts

### Endpoint: POST /api/v1/resource
**Request**:
```json
{
  "field": "value"
}
```

**Response**:
```json
{
  "success": true,
  "data": {}
}
```

## Security Considerations
- [Security measure 1]
- [Security measure 2]

## Performance Strategy
- [Optimization 1]
- [Caching strategy]
- [Query optimization]

## Error Handling
- [Error scenario 1]: [Handling approach]
- [Error scenario 2]: [Handling approach]

## Technology Choices
- **[Technology]**: [Justification]
```

## Example

```
/design api-rate-limiting
```

This will:
1. Read requirements from `.kiro/specs/api-rate-limiting/requirements.md`
2. Design rate limiter architecture
3. Specify database schema for tracking
4. Define middleware implementation
5. Plan caching strategy

## Integration Points

- Reads from `.kiro/specs/[feature-name]/requirements.md`
- Follows patterns in `.kiro/steering/`
- Uses Laravel conventions:
  - Service layer pattern
  - Eloquent ORM
  - Form requests
  - Jobs and queues
- Respects addon architecture if applicable

## Next Steps

After design is generated:
1. Review and iterate if needed
2. Use `/tasks [feature-name]` to create task breakdown
3. Or use `/sdd [feature-name]` to regenerate all specs

## Best Practices

- Follow project architecture patterns
- Design for maintainability
- Consider scalability
- Plan for errors and edge cases
- Document technology choices with reasoning
- Include security from the start
- Design APIs with versioning in mind
- Plan database indexes for performance
