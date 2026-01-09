# Capability: Interactive Components

## Overview
Implement reusable Livewire components for common interactive UI patterns, replacing jQuery-based implementations with server-driven reactive components.

---

## ADDED Requirements

### Requirement: DataTable Component
**Priority**: High  
**Rationale**: Replaces jQuery DataTables with server-side Livewire component for better performance and maintainability.

A reusable DataTable component MUST be implemented to handle tabular data display with server-side pagination, sorting, filtering, and row actions, replacing existing client-side jQuery DataTables.

#### Scenario: Render paginated data table
**Given** a Livewire DataTable component is created  
**And** the component receives a model class and column definitions  
**When** the component renders  
**Then** a table is displayed with the specified columns  
**And** data is paginated with configurable items per page  
**And** pagination controls are displayed at the bottom

#### Scenario: Sort table by column
**Given** a DataTable component is rendered  
**When** the user clicks a sortable column header  
**Then** the table data is re-sorted by that column  
**And** a sort indicator (↑ or ↓) is displayed  
**And** the sort state persists across page refreshes

#### Scenario: Search/filter table data
**Given** a DataTable component with search enabled  
**When** the user types in the search input  
**Then** the table data is filtered in real-time (debounced 500ms)  
**And** the pagination resets to page 1  
**And** a "no results" message is shown if no matches found

#### Scenario: Execute row action
**Given** a DataTable component with row actions defined  
**When** the user clicks an action button (e.g., "Delete")  
**Then** a confirmation modal is shown (if configured)  
**And** the action is executed via the service layer  
**And** the table data is refreshed  
**And** a success notification is displayed

#### Scenario: Execute bulk action
**Given** a DataTable component with bulk actions enabled  
**When** the user selects multiple rows via checkboxes  
**And** clicks a bulk action button (e.g., "Delete Selected")  
**Then** a confirmation modal is shown with the count of selected items  
**And** the bulk action is executed for all selected rows  
**And** the table data is refreshed  
**And** a success notification is displayed

---

### Requirement: Modal Component
**Priority**: High  
**Rationale**: Provides a reusable modal dialog for forms, confirmations, and content display.

A reusable Modal component MUST be implemented to provide standard dialog functionality including dynamic content loading, form handling, and confirmation flows.

#### Scenario: Open modal with dynamic content
**Given** a Modal component is included in the view  
**When** the user triggers a modal open event (e.g., clicks "Add User")  
**Then** the modal is displayed with a backdrop  
**And** the modal contains the specified title and content  
**And** the page scroll is disabled

#### Scenario: Submit form in modal
**Given** a Modal component contains a form  
**When** the user fills out the form and clicks "Submit"  
**Then** the form data is validated  
**And** if valid, the data is submitted via the service layer  
**And** the modal is closed  
**And** a success notification is displayed  
**And** the parent view is refreshed (if needed)

#### Scenario: Close modal on backdrop click
**Given** a Modal component with `closeOnBackdrop` set to true  
**When** the user clicks the backdrop (outside the modal)  
**Then** the modal is closed  
**And** any unsaved changes are discarded (or a confirmation is shown)

#### Scenario: Display confirmation dialog
**Given** a Modal component configured as a confirmation dialog  
**When** the user triggers a dangerous action (e.g., "Delete User")  
**Then** a confirmation modal is displayed  
**And** the modal shows the action description and consequences  
**And** the modal has "Cancel" and "Confirm" buttons  
**When** the user clicks "Confirm"  
**Then** the action is executed  
**And** the modal is closed

---

### Requirement: FormWizard Component
**Priority**: Medium  
**Rationale**: Simplifies multi-step forms with validation and progress tracking.

A FormWizard component MUST be implemented to handle multi-step workflows, managing state between steps and validating data at each stage.

#### Scenario: Display multi-step form
**Given** a FormWizard component with 3 steps defined  
**When** the component renders  
**Then** step 1 is displayed  
**And** a progress indicator shows "Step 1 of 3"  
**And** "Next" button is enabled  
**And** "Previous" button is disabled

#### Scenario: Navigate to next step
**Given** a FormWizard component on step 1  
**When** the user fills out the form fields  
**And** clicks "Next"  
**Then** the form data is validated  
**And** if valid, step 2 is displayed  
**And** the progress indicator updates to "Step 2 of 3"  
**And** "Previous" button is now enabled

#### Scenario: Navigate to previous step
**Given** a FormWizard component on step 2  
**When** the user clicks "Previous"  
**Then** step 1 is displayed  
**And** previously entered data is preserved  
**And** no validation is performed

#### Scenario: Submit wizard
**Given** a FormWizard component on the final step  
**When** the user fills out the form fields  
**And** clicks "Submit"  
**Then** all form data is validated  
**And** if valid, the data is submitted via the service layer  
**And** a success notification is displayed  
**And** the user is redirected to the appropriate page

---

### Requirement: Notifications Component
**Priority**: High  
**Rationale**: Provides consistent user feedback for actions and events.

A global Notifications component MUST be implemented to display toast messages for success, error, warning, and info states, supporting stacking and auto-dismissal.

#### Scenario: Display success notification
**Given** a Notifications component is included in the layout  
**When** an action completes successfully  
**Then** a success notification is displayed (green background)  
**And** the notification includes the success message  
**And** the notification auto-dismisses after 3 seconds  
**And** the notification can be manually dismissed by clicking the close button

#### Scenario: Display error notification
**Given** a Notifications component is included in the layout  
**When** an action fails  
**Then** an error notification is displayed (red background)  
**And** the notification includes the error message  
**And** the notification does NOT auto-dismiss  
**And** the notification can be manually dismissed by clicking the close button

#### Scenario: Stack multiple notifications
**Given** a Notifications component is displayed  
**When** multiple actions trigger notifications in quick succession  
**Then** all notifications are displayed in a stack  
**And** newer notifications appear at the top  
**And** each notification auto-dismisses independently

---

### Requirement: ToggleSwitch Component
**Priority**: Medium  
**Rationale**: Provides a reusable toggle for status fields with confirmation and error handling.

A ToggleSwitch component MUST be implemented for binary state changes, supporting optimistic UI updates, confirmation dialogs, and error rollback.

#### Scenario: Toggle status with confirmation
**Given** a ToggleSwitch component for a gateway's "enabled" field  
**When** the user clicks the toggle  
**Then** a confirmation modal is displayed (if configured)  
**And** the modal shows the action description (e.g., "Enable this gateway?")  
**When** the user confirms  
**Then** the field is updated via the service layer  
**And** the toggle UI updates optimistically  
**And** a success notification is displayed

#### Scenario: Toggle status with error handling
**Given** a ToggleSwitch component  
**When** the user toggles the switch  
**And** the service layer throws an error  
**Then** the toggle UI reverts to the original state  
**And** an error notification is displayed  
**And** the error message is logged

---

## MODIFIED Requirements

_No existing requirements are modified by this capability._

---

## Testing Requirements

### Requirement: Component Unit Tests
**Priority**: High

#### Scenario: Test DataTable component methods
**Given** a DataTable component test  
**When** the test calls `sortBy('name')`  
**Then** the component's `$sortField` property is set to 'name'  
**And** the component's `$sortDirection` property is set to 'asc'  
**And** the component re-renders with sorted data

#### Scenario: Test Modal component events
**Given** a Modal component test  
**When** the test dispatches a 'modal-opened' event  
**Then** the modal's `$isOpen` property is set to true  
**And** the modal view is rendered

### Requirement: Component Browser Tests
**Priority**: Medium

#### Scenario: Test DataTable user interaction
**Given** a browser test for the users table  
**When** the test visits `/admin/users`  
**And** clicks the "Name" column header  
**Then** the table is re-sorted by name  
**And** the sort indicator is displayed

#### Scenario: Test Modal form submission
**Given** a browser test for the add user modal  
**When** the test clicks "Add User"  
**And** fills out the form  
**And** clicks "Submit"  
**Then** the modal closes  
**And** the new user appears in the table  
**And** a success notification is displayed
