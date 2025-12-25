# Market Status Validation

<cite>
**Referenced Files in This Document**
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php)
- [RiskManagementJob.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php)
- [trading-management.php](file://main/addons/trading-management-addon/config/trading-management.php)
- [market-closed-handling.md](file://docs/market-closed-handling.md)
- [TradingPreset.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Models/TradingPreset.php)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Core Components](#core-components)
4. [Architecture Overview](#architecture-overview)
5. [Detailed Component Analysis](#detailed-component-analysis)
6. [Dependency Analysis](#dependency-analysis)
7. [Performance Considerations](#performance-considerations)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Conclusion](#conclusion)

## Introduction
This document explains the Market Status Validation system that prevents trading when market data is stale or markets are closed. It focuses on the proactive validation performed before execution, the logging improvements for diagnosing root causes, and the integration points across the trading pipeline. The solution leverages Redis-cached MetaAPI candles to compute data freshness per symbol and timeframe, and it integrates seamlessly with the execution worker and risk management jobs.

## Project Structure
The market status validation spans three primary areas:
- Risk management job prepares execution data and passes it to the execution worker.
- Execution worker validates market freshness before placing orders and logs actionable diagnostics.
- Market status checker encapsulates the logic to determine whether market data is fresh enough for trading.

```mermaid
graph TB
RMJ["RiskManagementJob<br/>Prepares execution data"] --> EXJ["ExecutionJob<br/>Dispatches and executes"]
EXJ --> MSC["MarketStatusChecker<br/>Checks data freshness"]
MSC --> REDIS["Redis<br/>Cached candles"]
EXJ --> LOGS["Bot-specific logs<br/>storage/logs/trading-bot-{id}.log"]
```

**Diagram sources**
- [RiskManagementJob.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php#L139-L152)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L72-L92)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L46-L85)
- [trading-management.php](file://main/addons/trading-management-addon/config/trading-management.php#L45-L59)

**Section sources**
- [RiskManagementJob.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php#L139-L152)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L72-L92)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L46-L85)
- [trading-management.php](file://main/addons/trading-management-addon/config/trading-management.php#L45-L59)

## Core Components
- MarketStatusChecker: Computes market data freshness by checking the latest candle timestamp in Redis for MetaAPI streams and compares it to timeframe-specific thresholds. It returns a structured result indicating whether trading should proceed and provides detailed diagnostics.
- ExecutionJob: Orchestrates trade execution. It sets up bot-specific logging, validates market status before execution (except in test mode), and records execution logs for tracking.
- RiskManagementJob: Builds execution data including symbol, timeframe, and test_mode flags, then dispatches to the execution worker.

Key behaviors:
- Freshness thresholds vary by timeframe to reflect appropriate tolerance for higher-frequency charts.
- Test mode bypasses validation to allow immediate execution for testing.
- Detailed logs include data age, last candle timestamp, and actionable recommendations.

**Section sources**
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L20-L27)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L114-L185)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L55-L92)
- [RiskManagementJob.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php#L139-L152)

## Architecture Overview
The validation pipeline ensures that trading decisions are made only when market data is fresh. The flow below maps the actual code components and their interactions.

```mermaid
sequenceDiagram
participant RMJ as "RiskManagementJob"
participant EXJ as "ExecutionJob"
participant MSC as "MarketStatusChecker"
participant REDIS as "Redis"
participant LOG as "Bot Logs"
RMJ->>RMJ : "Build executionData<br/>includes timeframe, test_mode"
RMJ->>EXJ : "Dispatch with executionData"
EXJ->>LOG : "Setup bot-specific logging"
EXJ->>MSC : "validateTradeExecution(executionData, accountId, test_mode)"
MSC->>REDIS : "Fetch latest candle for account : symbol : timeframe"
REDIS-->>MSC : "Latest candle JSON"
MSC->>MSC : "Compute age_minutes vs threshold"
MSC-->>EXJ : "Validation result {should_proceed, reason, freshness_check}"
alt "should_proceed = false"
EXJ->>LOG : "Log market closed/stale error with diagnostics"
EXJ->>EXJ : "Create failed execution log"
else "should_proceed = true"
EXJ->>EXJ : "Place order via adapter"
EXJ->>LOG : "Log success/failure and position creation"
end
```

**Diagram sources**
- [RiskManagementJob.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php#L139-L152)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L72-L92)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L583-L618)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L46-L85)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L114-L185)

## Detailed Component Analysis

### MarketStatusChecker
Responsibilities:
- Determine maximum allowed age per timeframe.
- Fetch the latest candle from Redis using a MetaAPI stream key pattern.
- Compute age in minutes and decide if data is fresh.
- Return structured validation results with reason and status.
- Provide human-readable rejection reasons.

Important behaviors:
- Uses Redis list range to retrieve the most recent candle and parses its timestamp.
- Falls back to “no data” when Redis lookup fails or yields empty results.
- Treats missing symbol as an immediate rejection.
- Skips validation in test mode and returns a pass-through result.

```mermaid
flowchart TD
Start(["validateTradeExecution"]) --> CheckTest["Is test_mode?"]
CheckTest --> |Yes| PassThrough["Return should_proceed=true<br/>reason=test_mode"]
CheckTest --> |No| CheckSymbol["Has symbol?"]
CheckSymbol --> |No| RejectNoSymbol["Return should_proceed=false<br/>reason=missing symbol"]
CheckSymbol --> |Yes| Freshness["checkMarketDataFreshness(symbol, timeframe, accountId, botId)"]
Freshness --> IsFresh{"is_fresh?"}
IsFresh --> |No| BuildReason["buildRejectionReason(freshness_check)"]
BuildReason --> LogWarn["Log warning with diagnostics"]
LogWarn --> ReturnReject["Return should_proceed=false,<br/>reason, freshness_check"]
IsFresh --> |Yes| LogInfo["Log info: data is fresh"]
LogInfo --> ReturnPass["Return should_proceed=true,<br/>reason=fresh,<br/>freshness_check"]
```

**Diagram sources**
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L114-L185)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L195-L217)

**Section sources**
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L20-L27)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L38-L112)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L114-L185)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L195-L217)

### ExecutionJob
Responsibilities:
- Set up bot-specific logging to a dedicated log file for easier troubleshooting.
- Validate market status before execution (production only).
- Dispatch execution logs regardless of outcome for tracking.
- Place orders via adapters and record success/failure.
- Detect and log market closed errors distinctly.

Key integration points:
- Reads timeframe and test_mode from execution data.
- Uses MarketStatusChecker to gate execution.
- Creates execution logs for both success and failure scenarios.

```mermaid
sequenceDiagram
participant EXJ as "ExecutionJob.handle()"
participant MSC as "MarketStatusChecker"
participant AD as "Adapter"
participant LOG as "Logs"
EXJ->>EXJ : "setupBotLogger(bot_id)"
EXJ->>EXJ : "Load ExecutionConnection"
EXJ->>EXJ : "Skip validation if test_mode"
EXJ->>MSC : "validateTradeExecution(executionData, accountId, false)"
MSC-->>EXJ : "{should_proceed, reason, freshness_check}"
alt "should_proceed=false"
EXJ->>LOG : "logMarketClosedError(validation, connection, bot_id)"
EXJ->>EXJ : "Create failed execution log"
else "should_proceed=true"
EXJ->>AD : "createLimitOrder or createMarketOrder"
AD-->>EXJ : "Result {success, order_id, position_id}"
EXJ->>LOG : "Log success/failure"
EXJ->>EXJ : "Create position and update stats"
end
```

**Diagram sources**
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L55-L92)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L583-L618)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L172-L264)

**Section sources**
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L41-L92)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L583-L618)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L172-L264)

### RiskManagementJob
Responsibilities:
- Prepare execution data for the execution worker, including symbol, timeframe, direction, quantities, and SL/TP.
- Inject test_mode and timeframe into execution data so MarketStatusChecker can apply appropriate validation.
- Dispatch the execution job with validated data.

Impact on market validation:
- Ensures timeframe is present for freshness checks.
- Enables test_mode to bypass validation for testing scenarios.

**Section sources**
- [RiskManagementJob.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php#L139-L152)

### Configuration and Environment
- MetaAPI streaming Redis prefix is configurable and used by MarketStatusChecker to construct cache keys.
- Streaming settings include TTL and reconnect parameters that influence data freshness.

**Section sources**
- [trading-management.php](file://main/addons/trading-management-addon/config/trading-management.php#L45-L59)

### Preset-based Trading Hours (Context)
While not part of the core validation logic, presets define trading hours and timezones that can be considered alongside market data freshness. The preset model includes fields for trading hours, timezone, and session profiles.

**Section sources**
- [TradingPreset.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Models/TradingPreset.php#L49-L54)

## Dependency Analysis
The following diagram shows how components depend on each other and external systems.

```mermaid
graph LR
RMJ["RiskManagementJob"] --> EXJ["ExecutionJob"]
EXJ --> MSC["MarketStatusChecker"]
MSC --> REDIS["Redis"]
EXJ --> LOG["Bot Logs"]
EXJ --> AD["Exchange Adapter"]
MSC --> CFG["Config: trading-management.php"]
```

**Diagram sources**
- [RiskManagementJob.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php#L139-L152)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L72-L92)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L46-L85)
- [trading-management.php](file://main/addons/trading-management-addon/config/trading-management.php#L45-L59)

**Section sources**
- [RiskManagementJob.php](file://main/addons/trading-management-addon/Modules/RiskManagement/Jobs/RiskManagementJob.php#L139-L152)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L72-L92)
- [MarketStatusChecker.php](file://main/addons/trading-management-addon/Modules/Execution/Services/MarketStatusChecker.php#L46-L85)
- [trading-management.php](file://main/addons/trading-management-addon/config/trading-management.php#L45-L59)

## Performance Considerations
- Redis access is O(1) for fetching the latest candle via list range.
- Timeframe thresholds are constant-time comparisons.
- Logging overhead is minimal compared to broker API calls.
- Test mode avoids validation to reduce latency during development.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common scenarios and resolutions:
- Market closed or data stale:
  - The system logs a detailed error with data age, last candle timestamp, and recommendation to wait for the market to open or check the data stream.
  - Execution logs are created for tracking.
- Market closed error during execution:
  - The system detects market closed errors and logs a contextual message with a recommendation to retry when the market reopens.
- Test mode:
  - Validation is skipped to allow immediate execution for testing. Ensure test_mode is not enabled in production.

Operational tips:
- Verify Redis connectivity and that MetaAPI streaming is active for the account/symbol/timeframe.
- Confirm the Redis prefix configuration matches the streaming settings.
- Review bot-specific logs for detailed diagnostics.

**Section sources**
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L583-L618)
- [ExecutionJob.php](file://main/addons/trading-management-addon/Modules/Execution/Jobs/ExecutionJob.php#L234-L264)
- [market-closed-handling.md](file://docs/market-closed-handling.md#L164-L208)

## Conclusion
The Market Status Validation system proactively prevents trading when market data is stale or markets are closed. By integrating Redis-cached MetaAPI candles, configurable timeframe thresholds, and bot-specific logging, it delivers actionable diagnostics and robust protection for production trading while preserving flexibility for testing.