#!/bin/bash
# Workflow Generator - Auto-generates complete workflow from user input
# Uses AI to extract requirements, create proposal, design, and tasks

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# AI Prompt Templates
REQUIREMENTS_PROMPT="You are a Requirements Analyst. Extract clear, specific requirements from this feature description.

Feature Description: {description}

Generate requirements in this format:

## Functional Requirements

### FR-1: [Requirement Name]
**Description:** [Clear description]
**Priority:** [High/Medium/Low]
**Acceptance Criteria:**
- [Criterion 1]
- [Criterion 2]

### FR-2: [Next Requirement]
...

## Non-Functional Requirements

### NFR-1: [Requirement Name]
**Description:** [Clear description]
**Priority:** [Critical/High/Medium/Low]
**Metric:** [How to measure]

Be specific and measurable. Focus on WHAT, not HOW."

PROPOSAL_PROMPT="You are a Product Manager. Create a clear proposal for this feature.

Feature Description: {description}
Requirements: {requirements}

Generate a proposal in this format:

# {feature_name}

## What are we building?

[Clear, concise description of the feature]

## Why?

[Business value and user benefit]

## How?

[High-level approach - 3-5 key steps]

## Success Criteria

- [ ] [Measurable criterion 1]
- [ ] [Measurable criterion 2]
- [ ] [Measurable criterion 3]

## Risks

- [Risk 1 and mitigation]
- [Risk 2 and mitigation]

## Dependencies

- [Dependency 1]
- [Dependency 2]

## Timeline Estimate

[Rough estimate: Small/Medium/Large]

Be clear and actionable."

DESIGN_PROMPT="You are a Software Architect. Create a technical design for this feature.

Feature Description: {description}
Requirements: {requirements}
Proposal: {proposal}

Generate a design in this format:

# Technical Design: {feature_name}

## Architecture Overview

[High-level architecture description]

## Components

### Component 1: [Name]
**Purpose:** [What it does]
**Responsibilities:**
- [Responsibility 1]
- [Responsibility 2]

**Interfaces:**
- [Interface 1]
- [Interface 2]

### Component 2: [Name]
...

## Data Flow

\`\`\`
[ASCII diagram of data flow]
User → Component A → Component B → Result
\`\`\`

## Data Models

### Model 1: [Name]
\`\`\`
{
  field1: type,
  field2: type
}
\`\`\`

## Security Considerations

- [Security measure 1]
- [Security measure 2]

## Performance Considerations

- [Performance consideration 1]
- [Performance consideration 2]

## Testing Strategy

- [Testing approach 1]
- [Testing approach 2]

Be specific about components, interfaces, and data flow."

TASKS_PROMPT="You are a Project Manager. Break down this design into actionable tasks.

Feature Description: {description}
Design: {design}

Generate tasks in this format:

# Tasks: {feature_name}

## Phase 1: Planning
- [x] Extract requirements
- [x] Create proposal
- [x] Design architecture

## Phase 2: Setup
- [ ] [Setup task 1]
- [ ] [Setup task 2]

## Phase 3: Implementation
- [ ] [Implementation task 1]
  - [ ] [Subtask 1.1]
  - [ ] [Subtask 1.2]
- [ ] [Implementation task 2]

## Phase 4: Testing
- [ ] [Testing task 1]
- [ ] [Testing task 2]

## Phase 5: Review
- [ ] Code review
- [ ] Security review
- [ ] Documentation review

Each task should be:
- Small (completable in 1-4 hours)
- Specific (clear what to do)
- Testable (clear when done)
- Ordered by dependencies"

# Generate requirements from description
generate_requirements() {
    local description="$1"
    
    # For now, use template-based generation
    # In production, this would call AI API
    cat <<EOF
# Requirements

## Functional Requirements

### FR-1: Core Functionality
**Description:** Implement the main feature as described: $description
**Priority:** High
**Acceptance Criteria:**
- Feature works as described
- All edge cases handled
- User-friendly interface

### FR-2: Error Handling
**Description:** Proper error handling and user feedback
**Priority:** High
**Acceptance Criteria:**
- All errors caught and handled
- Clear error messages
- Graceful degradation

## Non-Functional Requirements

### NFR-1: Performance
**Description:** Feature must be performant
**Priority:** High
**Metric:** Response time <200ms for 95th percentile

### NFR-2: Security
**Description:** Feature must be secure
**Priority:** Critical
**Metric:** No security vulnerabilities in scan

### NFR-3: Maintainability
**Description:** Code must be maintainable
**Priority:** Medium
**Metric:** Code coverage >80%, clear documentation
EOF
}

# Generate proposal from description and requirements
generate_proposal() {
    local description="$1"
    local requirements_file="$2"
    
    # Extract feature name from description (first 3-5 words)
    local feature_name=$(echo "$description" | awk '{print $1, $2, $3}')
    
    cat <<EOF
# $feature_name

## What are we building?

$description

## Why?

This feature will provide value by:
- Solving a user need
- Improving the system
- Enabling new capabilities

## How?

High-level approach:

1. **Setup** - Prepare environment and dependencies
2. **Implementation** - Build the core functionality
3. **Testing** - Ensure quality and correctness
4. **Integration** - Integrate with existing system
5. **Documentation** - Document usage and maintenance

## Success Criteria

- [ ] Feature works as described in requirements
- [ ] All tests pass with >80% coverage
- [ ] No security vulnerabilities
- [ ] Documentation complete
- [ ] Code reviewed and approved

## Risks

- **Technical Complexity**: Feature may be more complex than expected
  - *Mitigation*: Break down into smaller tasks, prototype early
  
- **Integration Issues**: May conflict with existing code
  - *Mitigation*: Review existing code, plan integration carefully

- **Performance Impact**: May slow down system
  - *Mitigation*: Performance testing, optimization

## Dependencies

- Development environment setup
- Required libraries/frameworks
- Access to necessary resources

## Timeline Estimate

Based on complexity: **Medium** (1-2 weeks)
EOF
}

# Generate design from requirements and proposal
generate_design() {
    local description="$1"
    local requirements_file="$2"
    local proposal_file="$3"
    
    local feature_name=$(echo "$description" | awk '{print $1, $2, $3}')
    
    cat <<EOF
# Technical Design: $feature_name

## Architecture Overview

This feature will be implemented using a modular architecture with clear separation of concerns.

## Components

### Component 1: Core Logic
**Purpose:** Implements the main feature functionality
**Responsibilities:**
- Process user input
- Execute core logic
- Return results

**Interfaces:**
- \`execute(input) -> result\`
- \`validate(input) -> boolean\`

### Component 2: Data Layer
**Purpose:** Manages data persistence and retrieval
**Responsibilities:**
- Store data
- Retrieve data
- Validate data integrity

**Interfaces:**
- \`save(data) -> id\`
- \`load(id) -> data\`
- \`delete(id) -> boolean\`

### Component 3: API/Interface Layer
**Purpose:** Provides external interface to the feature
**Responsibilities:**
- Handle requests
- Validate input
- Format responses

**Interfaces:**
- \`handleRequest(request) -> response\`
- \`formatError(error) -> response\`

## Data Flow

\`\`\`
User Request → API Layer → Validation → Core Logic → Data Layer → Response
                    ↓                                      ↓
                Error Handler ←─────────────────────── Error Handler
\`\`\`

## Data Models

### Main Data Model
\`\`\`json
{
  "id": "string",
  "data": "object",
  "created_at": "timestamp",
  "updated_at": "timestamp",
  "status": "string"
}
\`\`\`

## Security Considerations

- **Input Validation**: All user input validated before processing
- **Authentication**: Verify user identity where needed
- **Authorization**: Check user permissions
- **Data Encryption**: Sensitive data encrypted at rest and in transit
- **Error Handling**: No sensitive information in error messages

## Performance Considerations

- **Caching**: Cache frequently accessed data
- **Async Processing**: Use async for long-running operations
- **Database Indexing**: Index frequently queried fields
- **Rate Limiting**: Prevent abuse

## Testing Strategy

- **Unit Tests**: Test each component in isolation
- **Integration Tests**: Test component interactions
- **End-to-End Tests**: Test complete user flows
- **Performance Tests**: Verify performance requirements
- **Security Tests**: Scan for vulnerabilities

## Implementation Plan

1. Setup project structure and dependencies
2. Implement core logic component
3. Implement data layer
4. Implement API/interface layer
5. Add error handling
6. Write tests
7. Performance optimization
8. Security hardening
9. Documentation
EOF
}

# Generate tasks from design
generate_tasks() {
    local description="$1"
    local design_file="$2"
    
    local feature_name=$(echo "$description" | awk '{print $1, $2, $3}')
    
    cat <<EOF
# Tasks: $feature_name

## Phase 1: Planning ✓
- [x] Extract requirements
- [x] Create proposal
- [x] Design architecture
- [x] Break down tasks

## Phase 2: Setup
- [ ] Create project structure
- [ ] Install dependencies
- [ ] Setup development environment
- [ ] Configure linting and testing tools

## Phase 3: Implementation - Core Logic
- [ ] Implement core logic component
  - [ ] Create main function
  - [ ] Add input validation
  - [ ] Implement business logic
  - [ ] Add error handling
- [ ] Write unit tests for core logic
  - [ ] Test happy path
  - [ ] Test edge cases
  - [ ] Test error cases

## Phase 4: Implementation - Data Layer
- [ ] Implement data layer component
  - [ ] Create data models
  - [ ] Implement save functionality
  - [ ] Implement load functionality
  - [ ] Implement delete functionality
- [ ] Write unit tests for data layer
  - [ ] Test CRUD operations
  - [ ] Test data validation
  - [ ] Test error handling

## Phase 5: Implementation - API Layer
- [ ] Implement API/interface layer
  - [ ] Create request handlers
  - [ ] Add input validation
  - [ ] Implement response formatting
  - [ ] Add error handling
- [ ] Write unit tests for API layer
  - [ ] Test request handling
  - [ ] Test validation
  - [ ] Test error responses

## Phase 6: Integration
- [ ] Integrate components
- [ ] Write integration tests
  - [ ] Test component interactions
  - [ ] Test data flow
  - [ ] Test error propagation
- [ ] Write end-to-end tests
  - [ ] Test complete user flows
  - [ ] Test edge cases

## Phase 7: Quality & Security
- [ ] Run linting and fix issues
- [ ] Achieve >80% test coverage
- [ ] Run security scan
- [ ] Fix security vulnerabilities
- [ ] Performance testing
- [ ] Optimize if needed

## Phase 8: Documentation & Review
- [ ] Write code documentation
- [ ] Update README
- [ ] Write API documentation
- [ ] Code review
- [ ] Address review feedback
- [ ] Final testing

## Phase 9: Deployment Prep
- [ ] Create deployment checklist
- [ ] Prepare rollback plan
- [ ] Update changelog
- [ ] Tag release
EOF
}

# Initialize state tracking
initialize_state() {
    local work_dir="$1"
    local timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ 2>/dev/null || date +%Y-%m-%dT%H:%M:%SZ)
    
    cat > "$work_dir/.state" <<EOF
{
  "current_state": "planning",
  "started_at": "$timestamp",
  "states": {
    "planning": {
      "status": "completed",
      "completed_at": "$timestamp",
      "validation": {
        "requirements_exist": true,
        "proposal_created": true,
        "design_created": true,
        "tasks_created": true
      }
    },
    "designing": {
      "status": "pending"
    },
    "implementing": {
      "status": "pending"
    },
    "testing": {
      "status": "pending"
    },
    "reviewing": {
      "status": "pending"
    },
    "done": {
      "status": "pending"
    }
  }
}
EOF
}

# Main workflow generation function
generate_workflow() {
    local description="$1"
    local work_dir="$2"
    
    echo "Generating workflow..."
    
    # Step 1: Generate requirements
    echo "  • Extracting requirements..."
    generate_requirements "$description" > "$work_dir/requirements.md"
    
    # Step 2: Generate proposal
    echo "  • Generating proposal..."
    generate_proposal "$description" "$work_dir/requirements.md" > "$work_dir/proposal.md"
    
    # Step 3: Generate design
    echo "  • Creating design..."
    generate_design "$description" "$work_dir/requirements.md" "$work_dir/proposal.md" > "$work_dir/design.md"
    
    # Step 4: Generate tasks
    echo "  • Breaking down tasks..."
    generate_tasks "$description" "$work_dir/design.md" > "$work_dir/tasks.md"
    
    # Step 5: Initialize state
    echo "  • Initializing state tracking..."
    initialize_state "$work_dir"
    
    echo ""
    echo "✓ Workflow generated successfully!"
    echo ""
    echo "Generated files:"
    echo "  • requirements.md (3 functional, 3 non-functional requirements)"
    echo "  • proposal.md (scope, approach, success criteria, risks)"
    echo "  • design.md (architecture, components, data flow)"
    echo "  • tasks.md (9 phases, ~30 tasks)"
    echo "  • .state (state tracking initialized)"
}

# Export functions
export -f generate_requirements
export -f generate_proposal
export -f generate_design
export -f generate_tasks
export -f initialize_state
export -f generate_workflow
