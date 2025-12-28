# Capability: Form Validation

## Overview
Implement real-time form validation in Livewire components using Laravel's validation rules, providing immediate user feedback and reducing server round-trips.

---

## ADDED Requirements

### Requirement: Real-time Field Validation
**Priority**: High  
**Rationale**: Provides immediate feedback to users, improving UX and reducing form submission errors.

Form fields MUST be validated in real-time or on blur events to provide immediate feedback to users, ensuring they can correct errors before submitting the form.

#### Scenario: Validate field on blur
**Given** a Livewire form component with validation rules  
**When** the user fills out a field and moves to the next field (blur event)  
**Then** the field is validated against its rules  
**And** if invalid, an error message is displayed below the field  
**And** the field is highlighted with a red border  
**And** if valid, any previous error message is cleared

#### Scenario: Validate field on input (debounced)
**Given** a Livewire form component with real-time validation enabled  
**When** the user types in a field  
**Then** the field is validated after 500ms of inactivity (debounced)  
**And** validation feedback is displayed in real-time  
**And** the user can see validation errors before submitting the form

#### Scenario: Display validation errors
**Given** a form field with validation errors  
**When** the field is rendered  
**Then** the error message is displayed below the field in red text  
**And** the field has a red border  
**And** the error message is specific to the validation rule that failed

---

### Requirement: Form Submission Validation
**Priority**: High  
**Rationale**: Ensures all form data is validated before submission, preventing invalid data from reaching the service layer.

The entire form MUST be validated upon submission, preventing the action if any validation rules fail and providing a summary of errors.

#### Scenario: Validate entire form on submit
**Given** a Livewire form component  
**When** the user clicks the "Submit" button  
**Then** all form fields are validated  
**And** if any field is invalid, the form is not submitted  
**And** all validation errors are displayed  
**And** the first invalid field is focused  
**And** a summary error message is displayed at the top of the form

#### Scenario: Submit valid form
**Given** a Livewire form component with all valid data  
**When** the user clicks the "Submit" button  
**Then** the form data is validated  
**And** if valid, the data is submitted via the service layer  
**And** a loading indicator is displayed during submission  
**And** the submit button is disabled to prevent double-submission  
**And** on success, a success notification is displayed

#### Scenario: Handle server-side validation errors
**Given** a Livewire form component  
**When** the user submits the form  
**And** the service layer throws a validation exception  
**Then** the validation errors are displayed next to the relevant fields  
**And** the submit button is re-enabled  
**And** the loading indicator is hidden

---

### Requirement: Custom Validation Rules
**Priority**: Medium  
**Rationale**: Allows for domain-specific validation logic (e.g., unique exchange connection name).

Custom validation rules MUST be supported to handle domain-specific constraints that cannot be expressed with standard Laravel validation rules.

#### Scenario: Validate with custom rule
**Given** a Livewire form component with a custom validation rule  
**When** the user fills out a field that uses the custom rule  
**Then** the custom rule is executed  
**And** if the rule fails, a custom error message is displayed  
**And** the error message is specific to the business logic

#### Scenario: Validate dependent fields
**Given** a form with dependent fields (e.g., "password" and "password_confirmation")  
**When** the user changes the "password" field  
**Then** the "password_confirmation" field is re-validated  
**And** if the fields don't match, an error is displayed on "password_confirmation"

---

### Requirement: Async Validation
**Priority**: Medium  
**Rationale**: Enables validation that requires server-side checks (e.g., checking if a username is already taken).

Asynchronous validation MUST be supported for checks that require database queries or external API calls, providing visual feedback during the check.

#### Scenario: Validate field with async check
**Given** a Livewire form component with an async validation rule  
**When** the user fills out a field (e.g., "username")  
**Then** after 500ms of inactivity, an AJAX request is sent to check availability  
**And** a loading indicator is displayed next to the field  
**And** if the username is taken, an error message is displayed  
**And** if the username is available, a success indicator is displayed

---

### Requirement: Validation Error Styling
**Priority**: Medium  
**Rationale**: Provides consistent visual feedback for validation errors across all forms.

Validation states MUST be visually distinct and consistent, using specific CSS classes to indicate valid and invalid states.

#### Scenario: Style invalid field
**Given** a form field with a validation error  
**When** the field is rendered  
**Then** the field has a red border (CSS class: `border-red-500`)  
**And** the error message is displayed in red text (CSS class: `text-red-500`)  
**And** an error icon is displayed next to the field

#### Scenario: Style valid field
**Given** a form field that has been validated and is valid  
**When** the field is rendered  
**Then** the field has a green border (CSS class: `border-green-500`)  
**And** a success icon is displayed next to the field  
**And** no error message is displayed

---

## MODIFIED Requirements

_No existing requirements are modified by this capability._

---

## Testing Requirements

### Requirement: Validation Unit Tests
**Priority**: High

#### Scenario: Test field validation rules
**Given** a Livewire form component test  
**When** the test sets a field to an invalid value  
**And** calls the validation method  
**Then** the component's `$errors` property contains the expected error  
**And** the error message matches the validation rule

#### Scenario: Test form submission with invalid data
**Given** a Livewire form component test  
**When** the test sets invalid data  
**And** calls the `submit()` method  
**Then** the form is not submitted  
**And** validation errors are present  
**And** the service layer is not called

### Requirement: Validation Browser Tests
**Priority**: Medium

#### Scenario: Test real-time validation in browser
**Given** a browser test for a form  
**When** the test fills out a field with invalid data  
**And** moves to the next field (blur)  
**Then** an error message is displayed  
**And** the field has a red border

#### Scenario: Test form submission with validation errors
**Given** a browser test for a form  
**When** the test fills out the form with invalid data  
**And** clicks "Submit"  
**Then** validation errors are displayed  
**And** the form is not submitted  
**And** the first invalid field is focused
