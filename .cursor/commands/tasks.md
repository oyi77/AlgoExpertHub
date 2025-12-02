# /tasks Command

Break down feature into actionable development tasks.

## Usage

/tasks [feature-name]

## Purpose

Generate a task breakdown with estimates, dependencies, and clear action items for implementation.

## Workflow

1. Analyze specification and plan documents
2. Break down into small, manageable tasks (1-2 days max)
3. Define dependencies and estimates
4. Create task list with clear acceptance criteria

## Prerequisites

- Specification document (`specs/active/[feature-name]/spec.md`)
- Plan document (`specs/active/[feature-name]/plan.md`)

## Example

/tasks payment-system

## Output

- `specs/active/[feature-name]/tasks.md` - Task breakdown with estimates and dependencies

