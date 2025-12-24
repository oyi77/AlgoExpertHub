## ADDED Requirements

### Requirement: Exchange Connection Form Functionality
The system SHALL provide a working form for users to create exchange connections with all required fields populated and functional.

#### Scenario: Dropdowns show available options
- **WHEN** a user opens the exchange connection creation form
- **THEN** the Connection Type dropdown SHALL show options: Data Only, Execution Only, Both
- **AND** the Exchange Type dropdown SHALL show supported exchanges (Binance, Bybit, OKX, etc.)
- **AND** the Provider dropdown SHALL show available providers (Official API, MetaAPI, etc.)

#### Scenario: API credential fields appear after exchange selection
- **WHEN** a user selects an exchange from the Exchange Type dropdown
- **THEN** API credential fields SHALL appear (API Key, API Secret)
- **AND** additional fields SHALL appear if required by the exchange (Passphrase, Subaccount)
- **AND** fields SHALL have show/hide toggles for sensitive data

#### Scenario: Connection can be tested before saving
- **WHEN** a user fills in connection details and credentials
- **THEN** a "Test Connection" button SHALL be available
- **AND** clicking the button SHALL verify credentials with the exchange API
- **AND** test results SHALL be displayed (success or error message)

#### Scenario: Form submission creates connection
- **WHEN** a user fills the form and clicks "Create Connection"
- **THEN** the form SHALL validate all required fields
- **AND** credentials SHALL be encrypted before saving
- **AND** connection SHALL be saved to database
- **AND** user SHALL be redirected to connections list with success message

#### Scenario: Errors are handled gracefully
- **WHEN** form submission fails (invalid credentials, network error, etc.)
- **THEN** clear error messages SHALL be displayed
- **AND** form data SHALL be preserved (not lost)
- **AND** user SHALL be able to fix errors and retry

