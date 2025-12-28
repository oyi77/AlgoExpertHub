# signal-display Specification

## Purpose
TBD - created by archiving change optimize-trading-performance-and-ux. Update Purpose after archive.
## Requirements
### Requirement: Consistent Signal Data Presentation
The system SHALL display trading signals with high precision and clear status information to the user.

#### Scenario: Format signal prices
- **WHEN** a trading signal is displayed on the dashboard
- **THEN** prices (Entry, SL, TP) SHALL be formatted with appropriate decimal places (5 for Forex, 2 for Crypto/Gold)

#### Scenario: Signal outcome visualization
- **WHEN** a signal reaches an outcome
- **THEN** it SHALL be displayed with a descriptive, color-coded badge indicating the result

#### Scenario: Backtesting notifications
- **WHEN** a backtesting job completes
- **THEN** the system SHALL send a notification to the user with the results

