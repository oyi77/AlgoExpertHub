# Spec: Landing Page Switcher

## MODIFIED Requirements

### Requirement: Index View Resolution
The root route (`/`) MUST resolution based on the `landing_page` configuration. If a specific landing page is selected, it SHALL take precedence over the active theme's default `home` view.

#### Scenario: Selection of Specific Landing Page
- **Given** the `landing_page` configuration is set to `bot-sales`
- **When** the root URL (`/`) is visited
- **Then** the system SHALL render `frontend.landings.bot-sales.index`
- **And** it SHALL use the assets located in `public/asset/frontend/landings/bot-sales/`

#### Scenario: No Specific Landing Page Selected
- **Given** the `landing_page` configuration is set to `null`
- **When** the root URL (`/`) is visited
- **Then** the system SHALL render the active theme's `home` view (e.g., `frontend.trading-v1.home`)

## ADDED Requirements

### Requirement: Admin Landing Selector
The Admin Panel SHALL provide a user interface to select the active landing page from available templates.

#### Scenario: Saving Landing Page Setting
- **Given** an admin is on the Theme Settings page
- **When** they select a landing page from the dropdown and save
- **Then** the `configurations` table SHALL be updated with the selected value
- **And** the change SHALL reflect immediately on the frontend
