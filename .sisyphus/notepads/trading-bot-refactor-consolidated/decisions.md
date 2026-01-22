# Decisions Made - Trading Bot Refactor Consolidated

## [2026-01-21T16:28:41Z] Plan Initialization

### Architectural Decisions
- [None yet - just starting]

### Technical Decisions
- [2026-01-22] Pass `true` as 8th parameter to `InternalBrokerService::placeOrder()` in `ExecutionJob::createVirtualPosition()` to ensure trades are correctly flagged as paper trades in the database.
- Fixed ConfigManagerTest.php to match current schema (using preset ID instead of bot_id in presets table).
- Fixed ConfigManagerTest.php to use correct Cache::remember signature.
- Created missing factories in main app directory to support addon testing.
