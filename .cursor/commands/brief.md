# /brief Command

Create a lightweight 30-minute feature brief for rapid development start.

## Usage

/brief [feature-name] [description]

## Purpose

Generate a quick feature brief that captures the essential requirements and context in approximately 30 minutes. Perfect for 80% of features that don't require full SDD 2.0 workflow.

## Workflow

1. Analyze user request and existing codebase patterns
2. Create feature brief in `specs/active/[feature-name]/feature-brief.md`
3. Include: user story, requirements, technical notes, and quick start guidance

## Example

/brief user-authentication Add OAuth2 authentication with Google and GitHub

## Output

- `specs/active/[feature-name]/feature-brief.md` - Feature brief document

