# Execution Engine Schema

<cite>
**Referenced Files in This Document**   
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)
- [2025_01_29_100002_create_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100002_create_execution_positions_table.php)
- [2025_01_29_100003_create_execution_analytics_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100003_create_execution_analytics_table.php)
- [2025_12_05_121113_create_mt_accounts_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_05_121113_create_mt_accounts_table.php)
- [2025_12_02_120006_add_srm_fields_to_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_02_120006_add_srm_fields_to_execution_positions_table.php)
- [2025_12_05_121638_add_trailing_stop_fields_to_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_05_121638_add_trailing_stop_fields_to_execution_positions_table.php)
- [2025_01_29_100004_add_multi_tp_to_execution_positions.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100004_add_multi_tp_to_execution_positions.php)
- [2025_12_07_125651_add_extended_fields_to_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_07_125651_add_extended_fields_to_execution_connections_table.php)
- [2025_12_07_134706_add_copy_trading_enabled_to_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_07_134706_add_copy_trading_enabled_to_execution_connections_table.php)
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
This document provides comprehensive data model documentation for the Execution Engine schema, detailing the entity relationships, field definitions, constraints, and data access patterns for key components including ExecutionConnection, ExecutionLog, ExecutionPosition, ExecutionAnalytics, and MtAccount. The schema supports automated trading execution with robust security, analytics, and lifecycle management.

## Project Structure
The Execution Engine schema is implemented within the trading-management-addon module, with database migrations located in the `database/migrations` directory. The core schema components are defined across multiple migration files that establish the foundational tables and subsequent enhancements.

```mermaid
graph TD
A[Execution Engine Schema] --> B[Main Module]
B --> C[trading-management-addon]
C --> D[database/migrations]
D --> E[create_execution_connections_table.php]
D --> F[create_execution_logs_table.php]
D --> G[create_execution_positions_table.php]
D --> H[create_execution_analytics_table.php]
D --> I[create_mt_accounts_table.php]
D --> J[add_multi_tp_to_execution_positions.php]
D --> K[add_trailing_stop_fields_to_execution_positions.php]
D --> L[add_extended_fields_to_execution_connections_table.php]
```

**Diagram sources**
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)
- [2025_01_29_100002_create_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100002_create_execution_positions_table.php)

**Section sources**
- [main/addons/trading-management-addon/database/migrations](file://main/addons/trading-management-addon/database/migrations)

## Core Components
The Execution Engine schema consists of five primary entities that work together to manage trading execution workflows: ExecutionConnection, ExecutionLog, ExecutionPosition, ExecutionAnalytics, and MtAccount. These components form a cohesive system for managing trading connections, recording execution events, tracking open positions, analyzing performance, and interfacing with MetaTrader accounts.

**Section sources**
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)
- [2025_01_29_100002_create_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100002_create_execution_positions_table.php)
- [2025_01_29_100003_create_execution_analytics_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100003_create_execution_analytics_table.php)
- [2025_12_05_121113_create_mt_accounts_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_05_121113_create_mt_accounts_table.php)

## Architecture Overview
The Execution Engine schema follows a normalized relational design with clear entity relationships and referential integrity constraints. The architecture supports atomic execution operations, comprehensive analytics, and secure credential management.

```mermaid
erDiagram
EXECUTION_CONNECTIONS {
bigint id PK
bigint user_id FK
bigint admin_id FK
varchar name
enum type
varchar exchange_name
text credentials
enum status
boolean is_active
boolean is_admin_owned
text last_error
timestamp last_tested_at
timestamp last_used_at
json settings
enum connection_type
varchar provider
boolean data_fetching_enabled
boolean trade_execution_enabled
json data_settings
json execution_settings
timestamp last_data_fetch_at
timestamp last_trade_execution_at
boolean copy_trading_enabled
}
EXECUTION_LOGS {
bigint id PK
bigint signal_id FK
bigint connection_id FK
enum execution_type
varchar order_id
varchar symbol
enum direction
decimal quantity
decimal entry_price
decimal sl_price
decimal tp_price
enum status
timestamp executed_at
text error_message
json response_data
}
EXECUTION_POSITIONS {
bigint id PK
bigint signal_id FK
bigint connection_id FK
bigint execution_log_id FK
varchar order_id
varchar symbol
enum direction
decimal quantity
decimal entry_price
decimal current_price
decimal sl_price
decimal tp_price
enum status
decimal pnl
decimal pnl_percentage
timestamp closed_at
enum closed_reason
timestamp last_price_update_at
decimal predicted_slippage
decimal performance_score_at_entry
decimal srm_adjusted_lot
decimal srm_sl_buffer
text srm_adjustment_reason
boolean trailing_stop_enabled
decimal trailing_stop_distance
decimal trailing_stop_percentage
decimal highest_price
decimal lowest_price
boolean breakeven_enabled
decimal breakeven_trigger_price
boolean sl_moved_to_breakeven
decimal tp1_price
decimal tp2_price
decimal tp3_price
decimal tp1_close_pct
decimal tp2_close_pct
decimal tp3_close_pct
timestamp tp1_closed_at
timestamp tp2_closed_at
timestamp tp3_closed_at
decimal tp1_closed_qty
decimal tp2_closed_qty
decimal tp3_closed_qty
}
EXECUTION_ANALYTICS {
bigint id PK
bigint connection_id FK
bigint user_id FK
bigint admin_id FK
date date
int total_trades
int winning_trades
int losing_trades
decimal total_pnl
decimal win_rate
decimal profit_factor
decimal max_drawdown
decimal balance
decimal equity
json additional_metrics
}
MT_ACCOUNTS {
bigint id PK
bigint user_id FK
bigint admin_id FK
bigint execution_connection_id FK
enum platform
varchar account_number
varchar server
varchar broker_name
varchar api_key
varchar account_id
json credentials
decimal balance
decimal equity
decimal margin
decimal free_margin
varchar currency
int leverage
enum status
timestamp last_synced_at
text last_error
boolean is_active
}
EXECUTION_CONNECTIONS ||--o{ EXECUTION_LOGS : "has"
EXECUTION_CONNECTIONS ||--o{ EXECUTION_POSITIONS : "has"
EXECUTION_CONNECTIONS ||--o{ EXECUTION_ANALYTICS : "has"
EXECUTION_CONNECTIONS ||--o{ MT_ACCOUNTS : "has"
EXECUTION_LOGS ||--|| EXECUTION_POSITIONS : "links"
SIGNALS ||--o{ EXECUTION_LOGS : "triggers"
SIGNALS ||--o{ EXECUTION_POSITIONS : "triggers"
USERS ||--o{ EXECUTION_CONNECTIONS : "owns"
ADMINS ||--o{ EXECUTION_CONNECTIONS : "manages"
```

**Diagram sources**
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)
- [2025_01_29_100002_create_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100002_create_execution_positions_table.php)
- [2025_01_29_100003_create_execution_analytics_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100003_create_execution_analytics_table.php)
- [2025_12_05_121113_create_mt_accounts_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_05_121113_create_mt_accounts_table.php)

## Detailed Component Analysis

### ExecutionConnection Analysis
The ExecutionConnection entity represents a trading connection to an exchange or broker, storing encrypted credentials and configuration settings. It serves as the foundation for all execution operations.

```mermaid
classDiagram
class ExecutionConnection {
+bigint id
+bigint user_id
+bigint admin_id
+varchar name
+enum type
+varchar exchange_name
+text credentials
+enum status
+boolean is_active
+boolean is_admin_owned
+text last_error
+timestamp last_tested_at
+timestamp last_used_at
+json settings
+enum connection_type
+varchar provider
+boolean data_fetching_enabled
+boolean trade_execution_enabled
+json data_settings
+json execution_settings
+timestamp last_data_fetch_at
+timestamp last_trade_execution_at
+boolean copy_trading_enabled
+timestamp created_at
+timestamp updated_at
}
ExecutionConnection --> Users : "user_id → id"
ExecutionConnection --> Admins : "admin_id → id"
ExecutionConnection --> ExecutionLogs : "id → connection_id"
ExecutionConnection --> ExecutionPositions : "id → connection_id"
ExecutionConnection --> ExecutionAnalytics : "id → connection_id"
ExecutionConnection --> MtAccounts : "id → execution_connection_id"
```

**Diagram sources**
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_12_07_125651_add_extended_fields_to_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_07_125651_add_extended_fields_to_execution_connections_table.php)
- [2025_12_07_134706_add_copy_trading_enabled_to_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_07_134706_add_copy_trading_enabled_to_execution_connections_table.php)

**Section sources**
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_12_07_125651_add_extended_fields_to_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_07_125651_add_extended_fields_to_execution_connections_table.php)

### ExecutionLog Analysis
The ExecutionLog entity records the execution events for trading signals, capturing the status and outcome of each execution attempt.

```mermaid
classDiagram
class ExecutionLog {
+bigint id
+bigint signal_id
+bigint connection_id
+enum execution_type
+varchar order_id
+varchar symbol
+enum direction
+decimal quantity
+decimal entry_price
+decimal sl_price
+decimal tp_price
+enum status
+timestamp executed_at
+text error_message
+json response_data
+timestamp created_at
+timestamp updated_at
}
ExecutionLog --> Signals : "signal_id → id"
ExecutionLog --> ExecutionConnection : "connection_id → id"
ExecutionLog --> ExecutionPosition : "id → execution_log_id"
```

**Diagram sources**
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)

**Section sources**
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)

### ExecutionPosition Analysis
The ExecutionPosition entity tracks open and closed trading positions with comprehensive risk management parameters including multi-take profit levels, trailing stops, and smart risk management adjustments.

```mermaid
classDiagram
class ExecutionPosition {
+bigint id
+bigint signal_id
+bigint connection_id
+bigint execution_log_id
+varchar order_id
+varchar symbol
+enum direction
+decimal quantity
+decimal entry_price
+decimal current_price
+decimal sl_price
+decimal tp_price
+enum status
+decimal pnl
+decimal pnl_percentage
+timestamp closed_at
+enum closed_reason
+timestamp last_price_update_at
+decimal predicted_slippage
+decimal performance_score_at_entry
+decimal srm_adjusted_lot
+decimal srm_sl_buffer
+text srm_adjustment_reason
+boolean trailing_stop_enabled
+decimal trailing_stop_distance
+decimal trailing_stop_percentage
+decimal highest_price
+decimal lowest_price
+boolean breakeven_enabled
+decimal breakeven_trigger_price
+boolean sl_moved_to_breakeven
+decimal tp1_price
+decimal tp2_price
+decimal tp3_price
+decimal tp1_close_pct
+decimal tp2_close_pct
+decimal tp3_close_pct
+timestamp tp1_closed_at
+timestamp tp2_closed_at
+timestamp tp3_closed_at
+decimal tp1_closed_qty
+decimal tp2_closed_qty
+decimal tp3_closed_qty
+timestamp created_at
+timestamp updated_at
}
ExecutionPosition --> Signals : "signal_id → id"
ExecutionPosition --> ExecutionConnection : "connection_id → id"
ExecutionPosition --> ExecutionLog : "execution_log_id → id"
```

**Diagram sources**
- [2025_01_29_100002_create_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100002_create_execution_positions_table.php)
- [2025_12_02_120006_add_srm_fields_to_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_02_120006_add_srm_fields_to_execution_positions_table.php)
- [2025_12_05_121638_add_trailing_stop_fields_to_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_05_121638_add_trailing_stop_fields_to_execution_positions_table.php)
- [2025_01_29_100004_add_multi_tp_to_execution_positions.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100004_add_multi_tp_to_execution_positions.php)

**Section sources**
- [2025_01_29_100002_create_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100002_create_execution_positions_table.php)
- [2025_12_02_120006_add_srm_fields_to_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_02_120006_add_srm_fields_to_execution_positions_table.php)
- [2025_12_05_121638_add_trailing_stop_fields_to_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_05_121638_add_trailing_stop_fields_to_execution_positions_table.php)
- [2025_01_29_100004_add_multi_tp_to_execution_positions.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100004_add_multi_tp_to_execution_positions.php)

### ExecutionAnalytics Analysis
The ExecutionAnalytics entity provides daily performance metrics for execution connections, enabling comprehensive performance analysis and reporting.

```mermaid
classDiagram
class ExecutionAnalytics {
+bigint id
+bigint connection_id
+bigint user_id
+bigint admin_id
+date date
+int total_trades
+int winning_trades
+int losing_trades
+decimal total_pnl
+decimal win_rate
+decimal profit_factor
+decimal max_drawdown
+decimal balance
+decimal equity
+json additional_metrics
+timestamp created_at
+timestamp updated_at
}
ExecutionAnalytics --> ExecutionConnection : "connection_id → id"
ExecutionAnalytics --> Users : "user_id → id"
ExecutionAnalytics --> Admins : "admin_id → id"
```

**Diagram sources**
- [2025_01_29_100003_create_execution_analytics_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100003_create_execution_analytics_table.php)

**Section sources**
- [2025_01_29_100003_create_execution_analytics_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100003_create_execution_analytics_table.php)

### MtAccount Analysis
The MtAccount entity represents MetaTrader accounts linked to execution connections, storing account-specific details and real-time balance information.

```mermaid
classDiagram
class MtAccount {
+bigint id
+bigint user_id
+bigint admin_id
+bigint execution_connection_id
+enum platform
+varchar account_number
+varchar server
+varchar broker_name
+varchar api_key
+varchar account_id
+json credentials
+decimal balance
+decimal equity
+decimal margin
+decimal free_margin
+varchar currency
+int leverage
+enum status
+timestamp last_synced_at
+text last_error
+boolean is_active
+timestamp created_at
+timestamp updated_at
}
MtAccount --> Users : "user_id → id"
MtAccount --> Admins : "admin_id → id"
MtAccount --> ExecutionConnection : "execution_connection_id → id"
```

**Diagram sources**
- [2025_12_05_121113_create_mt_accounts_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_05_121113_create_mt_accounts_table.php)

**Section sources**
- [2025_12_05_121113_create_mt_accounts_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_05_121113_create_mt_accounts_table.php)

## Dependency Analysis
The Execution Engine schema demonstrates a well-structured dependency graph with clear parent-child relationships. The ExecutionConnection serves as the central entity, with other components depending on it for referential integrity.

```mermaid
graph TD
A[Users/Admins] --> B[ExecutionConnection]
B --> C[ExecutionLog]
B --> D[ExecutionPosition]
B --> E[ExecutionAnalytics]
B --> F[MtAccount]
C --> D
G[Signals] --> C
G --> D
```

**Diagram sources**
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)
- [2025_01_29_100002_create_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100002_create_execution_positions_table.php)

**Section sources**
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)
- [2025_01_29_100002_create_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100002_create_execution_positions_table.php)

## Performance Considerations
The schema includes multiple indexes to optimize query performance for common access patterns:
- Indexes on foreign keys (user_id, admin_id, connection_id, signal_id)
- Indexes on status fields for filtering active/inactive records
- Indexes on timestamps for time-based queries
- Composite indexes on frequently queried field combinations
- Unique constraints to prevent duplicate records

The indexing strategy supports efficient queries for real-time position monitoring, execution performance analysis, and connection health tracking.

**Section sources**
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)
- [2025_01_29_100002_create_execution_positions_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100002_create_execution_positions_table.php)

## Troubleshooting Guide
Common issues and their resolutions:
- **Connection failures**: Check credentials encryption, verify API key validity, ensure network connectivity
- **Execution timeouts**: Verify exchange API rate limits, check server load, review execution settings
- **Position synchronization issues**: Validate MtAccount configuration, check last_synced_at timestamps, verify API connectivity
- **Analytics discrepancies**: Ensure daily aggregation jobs are running, verify data consistency between related tables
- **Performance degradation**: Monitor index usage, check for long-running queries, review database statistics

**Section sources**
- [2025_01_29_100000_create_execution_connections_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100000_create_execution_connections_table.php)
- [2025_01_29_100001_create_execution_logs_table.php](file://main/addons/trading-management-addon/database/migrations/2025_01_29_100001_create_execution_logs_table.php)
- [2025_12_05_121113_create_mt_accounts_table.php](file://main/addons/trading-management-addon/database/migrations/2025_12_05_121113_create_mt_accounts_table.php)

## Conclusion
The Execution Engine schema provides a robust foundation for automated trading execution with comprehensive support for connection management, execution logging, position tracking, performance analytics, and MetaTrader integration. The schema design emphasizes data integrity, security, and performance with appropriate constraints, indexes, and encryption mechanisms. The modular structure allows for extensibility through additional fields and tables while maintaining referential integrity across components.