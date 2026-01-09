# Spec: Service & Repository Layer

## ADDED Requirements

### Requirement: Service Layer Pattern
Controllers SHALL delegate all business logic to Service classes. Controllers are thin HTTP handlers that only handle request validation, delegate to services, and return responses.

#### Scenario: Business Logic in Services
- **Given** a Controller method
- **When** it processes a request
- **Then** it should ONLY handle request validation (optional), delegate to a Service, and return a response.
- **And** it must NOT contain complex conditionals, loops for business rules, or direct data manipulation.

### Requirement: Repository Layer Pattern
Data access operations SHALL be performed through Repository classes. Direct `DB::table()` calls or raw SQL queries are not allowed in Controllers or Services.

#### Scenario: DB Operations in Repositories
- **Given** a Service or Controller (legacy)
- **When** data access is required
- **Then** it must use a Repository method (or Model scope).
- **And** it must NOT use direct `DB::table()` calls or raw SQL.
