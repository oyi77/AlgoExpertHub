# AI Connection Management

<cite>
**Referenced Files in This Document**   
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php)
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php)
- [ConnectionRotationService.php](file://main/addons/ai-connection-addon/App/Services/ConnectionRotationService.php)
- [OpenAiAdapter.php](file://main/addons/ai-connection-addon/App/Providers/OpenAiAdapter.php)
- [GeminiAdapter.php](file://main/addons/ai-connection-addon/App/Providers/GeminiAdapter.php)
- [OpenRouterAdapter.php](file://main/addons/ai-connection-addon/App/Providers/OpenRouterAdapter.php)
- [AiProvider.php](file://main/addons/ai-connection-addon/App/Models/AiProvider.php)
- [AiConnectionUsage.php](file://main/addons/ai-connection-addon/App/Models/AiConnectionUsage.php)
- [ConnectionController.php](file://main/addons/ai-connection-addon/App/Http/Controllers/Backend/ConnectionController.php)
- [ProviderController.php](file://main/addons/ai-connection-addon/App/Http/Controllers/Backend/ProviderController.php)
- [UsageAnalyticsController.php](file://main/addons/ai-connection-addon/App/Http/Controllers/Backend/UsageAnalyticsController.php)
- [create_ai_connections_table.php](file://main/addons/ai-connection-addon/database/migrations/2025_12_03_100001_create_ai_connections_table.php)
- [create_ai_providers_table.php](file://main/addons/ai-connection-addon/database/migrations/2025_12_03_100000_create_ai_providers_table.php)
- [create_ai_connection_usage_table.php](file://main/addons/ai-connection-addon/database/migrations/2025_12_03_100002_create_ai_connection_usage_table.php)
- [add_default_connection_foreign_key.php](file://main/addons/ai-connection-addon/database/migrations/2025_12_03_100003_add_default_connection_foreign_key.php)
- [ProviderAdapterFactory.php](file://main/addons/ai-connection-addon/App/Services/ProviderAdapterFactory.php)
- [AiProviderInterface.php](file://main/addons/ai-connection-addon/App/Contracts/AiProviderInterface.php)
- [admin.php](file://main/addons/ai-connection-addon/routes/admin.php)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Architecture Overview](#architecture-overview)
3. [Core Components](#core-components)
4. [AiConnection Model Architecture](#aiconnection-model-architecture)
5. [Connection Pooling and Rotation](#connection-pooling-and-rotation)
6. [Credential Encryption and Security](#credential-encryption-and-security)
7. [AiConnectionService Methods](#aiconnectionservice-methods)
8. [Provider Integration and Adapters](#provider-integration-and-adapters)
9. [Admin Interface Management](#admin-interface-management)
10. [Configuration Options](#configuration-options)
11. [Troubleshooting Guide](#troubleshooting-guide)
12. [Conclusion](#conclusion)

## Introduction

The AI Connection Management system provides a centralized solution for handling AI provider connections in the application. This comprehensive system enables administrators to manage multiple AI service providers, configure connection settings, monitor usage, and ensure reliable AI service availability through sophisticated failover and rotation mechanisms. The system is designed to support various AI providers including OpenAI, Google Gemini, and OpenRouter, with extensible architecture for future provider integration.

The implementation follows Laravel best practices and leverages the framework's built-in encryption for secure credential storage. The system includes robust health monitoring, usage analytics, and automated failover capabilities to ensure high availability of AI services. This documentation provides a detailed overview of the architecture, components, and operational aspects of the AI connection management system.

**Section sources**
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L1-L296)
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php#L1-L310)

## Architecture Overview

The AI Connection Management system follows a modular architecture with clear separation of concerns. The system consists of several interconnected components that work together to provide reliable AI service connectivity.

```mermaid
graph TD
subgraph "Admin Interface"
A[Admin Dashboard] --> B[Connection Management]
A --> C[Provider Management]
A --> D[Usage Analytics]
end
subgraph "Core Services"
E[AiConnectionService] --> F[ConnectionRotationService]
E --> G[ProviderAdapterFactory]
F --> H[AiConnection]
G --> I[OpenAiAdapter]
G --> J[GeminiAdapter]
G --> K[OpenRouterAdapter]
end
subgraph "Data Layer"
H --> L[(ai_connections)]
M[AiProvider] --> L
N[AiConnectionUsage] --> L
end
B --> E
C --> M
D --> N
E --> |Execute AI Calls| I
E --> |Execute AI Calls| J
E --> |Execute AI Calls| K
style A fill:#f9f,stroke:#333
style E fill:#bbf,stroke:#333
style L fill:#f96,stroke:#333
```

**Diagram sources**
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php#L1-L310)
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L1-L296)
- [AiProvider.php](file://main/addons/ai-connection-addon/App/Models/AiProvider.php#L1-L82)

## Core Components

The AI Connection Management system consists of several core components that work together to provide a robust and reliable connection handling solution. These components include the AiConnection model, AiConnectionService, ConnectionRotationService, provider adapters, and the admin interface controllers.

The system is designed with extensibility in mind, allowing for easy addition of new AI providers through the adapter pattern. Each component has a specific responsibility, following the single responsibility principle. The AiConnection model handles data persistence and business logic related to individual connections, while the AiConnectionService orchestrates the overall connection management process.

The architecture supports multiple concurrent connections to the same provider, enabling load balancing and failover capabilities. Connection health is continuously monitored, and usage statistics are tracked for cost management and performance analysis. The system also provides comprehensive error handling and retry mechanisms to ensure service availability.

**Section sources**
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L1-L296)
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php#L1-L310)
- [ConnectionRotationService.php](file://main/addons/ai-connection-addon/App/Services/ConnectionRotationService.php#L1-L165)

## AiConnection Model Architecture

The AiConnection model represents an individual connection to an AI provider and contains all the necessary information to establish and maintain that connection. The model is designed with security, reliability, and performance in mind.

```mermaid
classDiagram
class AiConnection {
+int id
+int provider_id
+string name
+text credentials
+json settings
+string status
+int priority
+int rate_limit_per_minute
+int rate_limit_per_day
+timestamp last_used_at
+timestamp last_error_at
+int error_count
+int success_count
+timestamp created_at
+timestamp updated_at
+getProvider() AiProvider
+getUsageLogs() AiConnectionUsage[]
+recentUsage(days) AiConnectionUsage[]
+scopeActive(query) Query
+scopeByProvider(query, providerId) Query
+scopeByPriority(query) Query
+scopeHealthy(query, threshold) Query
+setCredentialsAttribute(value) void
+getCredentialsAttribute(value) array
+getCredential(key, default) mixed
+getApiKey() string
+getBaseUrl() string
+getModel() string
+isActive() bool
+hasErrors() bool
+recordSuccess() void
+recordError(message) void
+isRateLimited() bool
+getSuccessRateAttribute() float
+getHealthStatusAttribute() string
}
class AiProvider {
+int id
+string name
+string slug
+string status
+int default_connection_id
+getConnections() AiConnection[]
+getDefaultConnection() AiConnection
+getActiveConnections() AiConnection[]
+scopeActive(query) Query
+scopeBySlug(query, slug) Query
+isActive() bool
+getDisplayNameAttribute() string
}
class AiConnectionUsage {
+int id
+int connection_id
+string feature
+int tokens_used
+decimal cost
+boolean success
+int response_time_ms
+string error_message
+timestamp created_at
+getConnection() AiConnection
+scopeSuccessful(query) Query
+scopeFailed(query) Query
+scopeByFeature(query, feature) Query
+scopeByConnection(query, connectionId) Query
+scopeRecent(query, days) Query
+scopeToday(query) Query
+log(connectionId, feature, tokensUsed, cost, success, responseTimeMs, errorMessage) AiConnectionUsage
+getTotalCost(connectionId, days) float
+getTotalTokens(connectionId, days) int
+getUsageByFeature(days) array
+getAverageResponseTime(connectionId, days) float
}
AiConnection --> AiProvider : "belongsTo"
AiConnection --> AiConnectionUsage : "hasMany"
AiConnectionUsage --> AiConnection : "belongsTo"
```

**Diagram sources**
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L1-L296)
- [AiProvider.php](file://main/addons/ai-connection-addon/App/Models/AiProvider.php#L1-L82)
- [AiConnectionUsage.php](file://main/addons/ai-connection-addon/App/Models/AiConnectionUsage.php#L1-L187)

**Section sources**
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L1-L296)
- [create_ai_connections_table.php](file://main/addons/ai-connection-addon/database/migrations/2025_12_03_100001_create_ai_connections_table.php#L1-L47)

## Connection Pooling and Rotation

The connection pooling and rotation system provides intelligent load balancing and failover capabilities for AI provider connections. The ConnectionRotationService implements a priority-based rotation algorithm that selects the most appropriate connection based on health status, priority, and current load.

```mermaid
sequenceDiagram
participant Client
participant AiConnectionService
participant RotationService
participant Connection1
participant Connection2
Client->>AiConnectionService : execute(connectionId, prompt)
AiConnectionService->>AiConnectionService : Check rate limit
alt Rate limited
AiConnectionService->>RotationService : getNextConnection(providerId, excludeId)
RotationService->>RotationService : Query active, healthy connections
RotationService->>RotationService : Filter out rate limited
RotationService->>RotationService : Select by priority
RotationService-->>AiConnectionService : Alternative connection
AiConnectionService->>Connection2 : execute(prompt)
Connection2-->>AiConnectionService : Response
AiConnectionService-->>Client : Response
else Not rate limited
AiConnectionService->>Connection1 : execute(prompt)
Connection1-->>AiConnectionService : Response
AiConnectionService-->>Client : Response
end
alt Error occurs
Connection1->>AiConnectionService : Exception
AiConnectionService->>RotationService : getNextConnection(providerId, currentId)
RotationService-->>AiConnectionService : Fallback connection
AiConnectionService->>Connection2 : execute(prompt)
Connection2-->>AiConnectionService : Response
AiConnectionService-->>Client : Response
end
```

**Diagram sources**
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php#L1-L310)
- [ConnectionRotationService.php](file://main/addons/ai-connection-addon/App/Services/ConnectionRotationService.php#L1-L165)

**Section sources**
- [ConnectionRotationService.php](file://main/addons/ai-connection-addon/App/Services/ConnectionRotationService.php#L1-L165)
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php#L1-L310)

## Credential Encryption and Security

The system implements robust security measures for storing and handling AI provider credentials. All credentials are encrypted using Laravel's built-in encryption service before being stored in the database, ensuring that sensitive information is protected at rest.

```mermaid
flowchart TD
A[Raw Credentials] --> B{Is array?}
B --> |Yes| C[JSON Encode]
B --> |No| D[Use as is]
C --> E[Laravel Encrypt]
D --> E
E --> F[Store in database]
G[Retrieve from database] --> H[Laravel Decrypt]
H --> I{JSON Decode successful?}
I --> |Yes| J[Return array]
I --> |No| K{Is string?}
K --> |Yes| L[Return {'api_key': string}]
K --> |No| M[Return empty array]
J --> N[Use in API calls]
L --> N
M --> N
```

**Diagram sources**
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L1-L296)

**Section sources**
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L103-L147)

## AiConnectionService Methods

The AiConnectionService class provides the primary interface for interacting with AI connections. It orchestrates the connection management process, including execution of AI calls, connection testing, usage tracking, and failover handling.

```mermaid
classDiagram
class AiConnectionService {
-ConnectionRotationService rotationService
-ProviderAdapterFactory adapterFactory
+getAvailableConnections(providerSlug, activeOnly) Collection
+getNextConnection(providerId) AiConnection
+execute(connectionId, prompt, options, feature) array
+testConnection(connectionId) array
+trackUsage(data) void
+getConnection(connectionId) AiConnection
+getProvider(slug) AiProvider
+shouldAttemptRotation(exception) bool
+getUsageStatistics(connectionId, days) array
}
class ConnectionRotationService {
+getNextConnection(providerId, excludeId) AiConnection
+getBestConnection(providerId) AiConnection
+hasAvailableConnections(providerId) bool
+getFallbackConnection(providerId, primaryId) AiConnection
+reorderConnections(providerId, connectionIds) void
+resetErrorCounts(providerId) int
+getConnectionStatistics(providerId) array
}
AiConnectionService --> ConnectionRotationService : "dependency"
AiConnectionService --> ProviderAdapterFactory : "dependency"
```

**Diagram sources**
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php#L1-L310)
- [ConnectionRotationService.php](file://main/addons/ai-connection-addon/App/Services/ConnectionRotationService.php#L1-L165)

**Section sources**
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php#L1-L310)

## Provider Integration and Adapters

The system uses an adapter pattern to integrate with different AI providers, allowing for consistent interaction with various services while accommodating their unique requirements. Each provider has a dedicated adapter that implements the AiProviderInterface contract.

```mermaid
classDiagram
class AiProviderInterface {
<<interface>>
+execute(connection, prompt, options) array
+test(connection) array
+getAvailableModels(connection) array
+estimateCost(tokens, model) float
+getName() string
+getSlug() string
}
class OpenAiAdapter {
+execute(connection, prompt, options) array
+test(connection) array
+getAvailableModels(connection) array
+estimateCost(tokens, model) float
+getName() string
+getSlug() string
}
class GeminiAdapter {
+execute(connection, prompt, options) array
+test(connection) array
+getAvailableModels(connection) array
+estimateCost(tokens, model) float
+getName() string
+getSlug() string
}
class OpenRouterAdapter {
+execute(connection, prompt, options) array
+test(connection) array
+getAvailableModels(connection) array
+fetchModelMarketplace(connection) array
+estimateCost(tokens, model) float
+getName() string
+getSlug() string
}
AiProviderInterface <|-- OpenAiAdapter
AiProviderInterface <|-- GeminiAdapter
AiProviderInterface <|-- OpenRouterAdapter
ProviderAdapterFactory --> OpenAiAdapter : "creates"
ProviderAdapterFactory --> GeminiAdapter : "creates"
ProviderAdapterFactory --> OpenRouterAdapter : "creates"
```

**Diagram sources**
- [AiProviderInterface.php](file://main/addons/ai-connection-addon/App/Contracts/AiProviderInterface.php#L1-L59)
- [OpenAiAdapter.php](file://main/addons/ai-connection-addon/App/Providers/OpenAiAdapter.php#L1-L159)
- [GeminiAdapter.php](file://main/addons/ai-connection-addon/App/Providers/GeminiAdapter.php#L1-L162)
- [OpenRouterAdapter.php](file://main/addons/ai-connection-addon/App/Providers/OpenRouterAdapter.php#L1-L220)
- [ProviderAdapterFactory.php](file://main/addons/ai-connection-addon/App/Services/ProviderAdapterFactory.php#L1-L68)

**Section sources**
- [OpenAiAdapter.php](file://main/addons/ai-connection-addon/App/Providers/OpenAiAdapter.php#L1-L159)
- [GeminiAdapter.php](file://main/addons/ai-connection-addon/App/Providers/GeminiAdapter.php#L1-L162)
- [OpenRouterAdapter.php](file://main/addons/ai-connection-addon/App/Providers/OpenRouterAdapter.php#L1-L220)

## Admin Interface Management

The admin interface provides a comprehensive set of tools for managing AI connections and providers. The interface is accessible through dedicated routes and controllers that handle all CRUD operations and connection testing.

```mermaid
flowchart TD
A[Admin Dashboard] --> B[Providers]
A --> C[Connections]
A --> D[Usage Analytics]
B --> E[List Providers]
B --> F[Create Provider]
B --> G[Edit Provider]
B --> H[Delete Provider]
C --> I[List Connections]
C --> J[Create Connection]
C --> K[Edit Connection]
C --> L[Delete Connection]
C --> M[Test Connection]
C --> N[Toggle Status]
D --> O[View Analytics]
D --> P[Export Data]
I --> Q[Filter by Provider]
I --> R[Filter by Status]
I --> S[Sort by Priority]
J --> T[Validate Input]
J --> U[Encrypt Credentials]
J --> V[Store Connection]
M --> W[Call testConnection]
M --> X[Display Result]
style A fill:#f9f,stroke:#333
style B fill:#bbf,stroke:#333
style C fill:#bbf,stroke:#333
style D fill:#bbf,stroke:#333
```

**Diagram sources**
- [admin.php](file://main/addons/ai-connection-addon/routes/admin.php#L1-L46)
- [ConnectionController.php](file://main/addons/ai-connection-addon/App/Http/Controllers/Backend/ConnectionController.php#L1-L189)
- [ProviderController.php](file://main/addons/ai-connection-addon/App/Http/Controllers/Backend/ProviderController.php)

**Section sources**
- [ConnectionController.php](file://main/addons/ai-connection-addon/App/Http/Controllers/Backend/ConnectionController.php#L1-L189)
- [admin.php](file://main/addons/ai-connection-addon/routes/admin.php#L1-L46)

## Configuration Options

The AI connection system supports a comprehensive set of configuration options that allow fine-tuning of connection behavior, performance, and reliability. These options are stored in the connection's settings field as a JSON object.

```mermaid
erDiagram
ai_connections {
int id PK
int provider_id FK
string name
text credentials
json settings
enum status
int priority
int rate_limit_per_minute
int rate_limit_per_day
timestamp last_used_at
timestamp last_error_at
int error_count
int success_count
}
ai_providers {
int id PK
string name
string slug
string status
int default_connection_id FK
}
ai_connection_usage {
int id PK
int connection_id FK
string feature
int tokens_used
decimal cost
boolean success
int response_time_ms
string error_message
timestamp created_at
}
ai_connections ||--o{ ai_providers : "belongs to"
ai_connections ||--o{ ai_connection_usage : "has many"
ai_providers }o--|| ai_connections : "has default"
```

**Diagram sources**
- [create_ai_connections_table.php](file://main/addons/ai-connection-addon/database/migrations/2025_12_03_100001_create_ai_connections_table.php#L1-L47)
- [create_ai_providers_table.php](file://main/addons/ai-connection-addon/database/migrations/2025_12_03_100000_create_ai_providers_table.php)
- [create_ai_connection_usage_table.php](file://main/addons/ai-connection-addon/database/migrations/2025_12_03_100002_create_ai_connection_usage_table.php)

**Section sources**
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L1-L296)
- [AiProvider.php](file://main/addons/ai-connection-addon/App/Models/AiProvider.php#L1-L82)

## Troubleshooting Guide

This section provides guidance for diagnosing and resolving common issues with AI connections. The system includes comprehensive logging and monitoring capabilities to assist with troubleshooting.

```mermaid
flowchart TD
A[Connection Issue] --> B{Error Type}
B --> C[Authentication Failure]
C --> D[Verify API Key]
D --> E[Check Credentials]
E --> F[Retest Connection]
B --> G[Rate Limiting]
G --> H[Check Usage]
H --> I[Verify Rate Limits]
I --> J[Rotate Connection]
J --> K[Monitor Recovery]
B --> L[Network Timeout]
L --> M[Check Network]
M --> N[Test Connectivity]
N --> O[Verify Base URL]
O --> P[Adjust Timeout]
B --> Q[Service Unavailable]
Q --> R[Check Provider Status]
R --> S[Wait and Retry]
S --> T[Use Fallback]
F --> U{Resolved?}
J --> U
K --> U
P --> U
T --> U
U --> |Yes| V[Issue Resolved]
U --> |No| W[Contact Support]
style A fill:#f96,stroke:#333
style V fill:#9f9,stroke:#333
style W fill:#f96,stroke:#333
```

**Diagram sources**
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php#L270-L289)
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L222-L248)

**Section sources**
- [AiConnectionService.php](file://main/addons/ai-connection-addon/App/Services/AiConnectionService.php#L131-L164)
- [AiConnection.php](file://main/addons/ai-connection-addon/App/Models/AiConnection.php#L222-L248)

## Conclusion

The AI Connection Management system provides a robust, secure, and scalable solution for managing connections to multiple AI providers. The architecture is designed with reliability and maintainability in mind, following established design patterns and Laravel best practices.

Key features of the system include centralized connection management, automatic failover and rotation, secure credential storage with Laravel encryption, comprehensive usage tracking, and a user-friendly admin interface. The adapter pattern allows for easy integration of new AI providers, making the system extensible and future-proof.

The system's health monitoring and error handling capabilities ensure high availability of AI services, while the detailed usage analytics provide valuable insights for cost management and performance optimization. The comprehensive troubleshooting guidance helps administrators quickly diagnose and resolve common issues, minimizing downtime and service disruption.

Overall, the AI Connection Management system represents a sophisticated solution that effectively addresses the challenges of managing multiple AI provider connections in a production environment.