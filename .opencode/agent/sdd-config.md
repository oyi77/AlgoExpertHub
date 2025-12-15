# SDD Configuration for OpenCode Agents

**ALIGNMENT WITH .SDD**: This configuration aligns with and references the authoritative SDD guidelines in `.sdd/guidelines.md`.

## SDD Integration Guidelines

All OpenCode agents should follow these SDD practices:

1. **Spec Before Code**: Always create specifications before implementation
2. **Verify Before Build**: Get user approval on specs before writing code
3. **Tasks Before Work**: Break down work into manageable, tracked tasks
4. **Evolve as You Build**: Update specs when implementation reveals new insights

## Agent-Specific SDD Instructions

### Product Manager (@product-manager)
- Use `/sdd` command to generate initial requirements
- Focus on user stories and acceptance criteria
- Coordinate spec reviews with stakeholders

### QA Engineer (@qa-engineer)
- Review specs for testability
- Ensure acceptance criteria are measurable
- Create test plans based on requirements

### All Engineering Agents
- Reference `.sdd/templates/` for consistent documentation
- Store active specs in `.kiro/specs/[feature-name]/`
- Use task tracking (bd or Cursor) for implementation

## Best Practices

- Always verify specs with user before building
- Keep tasks small (1-2 days max)
- Update specs when requirements change
- Maintain version history for significant changes