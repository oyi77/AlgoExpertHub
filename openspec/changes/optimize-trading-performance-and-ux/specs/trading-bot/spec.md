## MODIFIED Requirements
### Requirement: Real-Time Bot Monitoring
The system SHALL provide real-time monitoring capabilities including status, health checks, worker status, and position updates.

#### Scenario: Monitor bot status
- **WHEN** a user views bot monitor
- **THEN** the system SHALL display current status, open positions, current P&L, and last activity

#### Scenario: Check bot health
- **WHEN** a health check is performed
- **THEN** the system SHALL verify worker status, exchange connection, data connection, and recent errors

#### Scenario: Broadcast status changes
- **WHEN** bot status changes (start, stop, pause)
- **THEN** the system SHALL broadcast the change event for real-time UI updates

#### Scenario: Auto-refresh monitoring data
- **WHEN** monitoring page is open
- **THEN** the system SHALL auto-refresh data every 5 seconds

#### Scenario: Display descriptive outcome badges
- **WHEN** a signal has an outcome (TP Hit, SL Hit, etc.)
- **THEN** the system SHALL display a color-coded status badge on the dashboard
