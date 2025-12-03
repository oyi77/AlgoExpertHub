# AI Connection Addon

## Overview
Centralized AI connection management system for the AlgoExpertHub platform. This addon provides a single source of truth for all AI credentials, supports credential rotation, rate limiting, and enables reuse across all platform features.

## Purpose
Consolidate AI provider management (OpenAI, Gemini, OpenRouter) into a centralized addon that:
- Stores and manages encrypted AI credentials
- Provides automatic connection rotation on rate limits
- Tracks usage and costs per feature
- Monitors connection health
- Exposes unified API for consumer addons

## Features

### Core Features
- **Provider Management**: Add and configure multiple AI providers (OpenAI, Gemini, OpenRouter)
- **Connection Management**: Multiple credentials per provider for rotation
- **Automatic Rotation**: Switch to backup connections when rate limits hit
- **Rate Limiting**: Track and enforce rate limits per connection
- **Usage Tracking**: Monitor tokens, costs, and response times
- **Health Monitoring**: Regular health checks and error tracking
- **Admin UI**: Complete CRUD interface for connections
- **Analytics Dashboard**: View usage by feature, provider, and time period

### Consumer API
Provides public API for other addons to execute AI calls:
```php
// Get available connections
AiConnectionService::getAvailableConnections('openai', $activeOnly = true)

// Execute AI call with auto-rotation
AiConnectionService::execute($connectionId, $prompt, $options = [])

// Test connection
AiConnectionService::testConnection($connectionId)

// Track usage
AiConnectionService::trackUsage($connectionId, $metrics)
```

## Architecture

### Database Schema

#### ai_providers
Stores AI provider definitions (OpenAI, Gemini, OpenRouter)
```sql
- id (bigint, primary key)
- name (string) - Display name
- slug (string, unique) - openai, gemini, openrouter
- status (enum: active, inactive)
- default_connection_id (bigint, nullable) - FK to ai_connections
- created_at, updated_at
```

#### ai_connections
Stores individual AI credentials and settings
```sql
- id (bigint, primary key)
- provider_id (bigint) - FK to ai_providers
- name (string) - e.g., "OpenAI Production", "OpenAI Backup"
- credentials (text, encrypted) - JSON: api_key, base_url, etc.
- settings (json) - model, temperature, timeout, etc.
- status (enum: active, inactive, error)
- priority (integer) - For rotation (1=primary, 2=secondary)
- rate_limit_per_minute (integer, nullable)
- rate_limit_per_day (integer, nullable)
- last_used_at (timestamp, nullable)
- last_error_at (timestamp, nullable)
- error_count (integer, default 0)
- success_count (integer, default 0)
- created_at, updated_at
```

#### ai_connection_usage
Tracks usage metrics per connection
```sql
- id (bigint, primary key)
- connection_id (bigint) - FK to ai_connections
- feature (string) - 'translation', 'parsing', 'market_analysis'
- tokens_used (integer)
- cost (decimal)
- success (boolean)
- response_time_ms (integer)
- error_message (text, nullable)
- created_at
```

### Provider Adapters

Each AI provider has an adapter implementing `AiProviderInterface`:
- `OpenAiAdapter` - OpenAI API integration
- `GeminiAdapter` - Google Gemini API integration
- `OpenRouterAdapter` - OpenRouter API integration

Interface methods:
```php
interface AiProviderInterface {
    public function execute(string $prompt, array $options): array;
    public function test(): bool;
    public function getModelList(): array;
    public function estimateCost(int $tokens): float;
}
```

### Services

#### AiConnectionService
Main service for consumer addons to use AI connections
- Get available connections
- Execute AI calls with rotation
- Track usage
- Handle errors

#### ConnectionRotationService
Manages automatic rotation logic
- Priority-based selection
- Rate limit detection
- Fallback to backup connections
- Error threshold tracking

#### ProviderAdapterFactory
Creates appropriate adapter based on provider
- Factory pattern
- Lazy loading
- Adapter caching

## Consumer Integration

### Multi-Channel Signal Addon
Replaces `ai_configurations` with `ai_parsing_profiles`:
```php
// Old (storing credentials)
ai_configurations:
- id, provider, api_key, model, settings

// New (referencing centralized connections)
ai_parsing_profiles:
- id, channel_source_id, ai_connection_id, parsing_prompt, settings
```

### Auto Translation Feature
Uses `translation_settings` to reference connections:
```php
translation_settings:
- id, ai_connection_id, fallback_connection_id, batch_size, delay_ms
```

### AI Trading Addon
Updates `ai_model_profiles` to reference connections:
```php
// Old
ai_model_profiles:
- id, name, provider, api_key, model, settings

// New
ai_model_profiles:
- id, name, ai_connection_id, settings
```

## Installation

### 1. Register Addon
Add to `app/Providers/AppServiceProvider.php`:
```php
protected function registerAddonServiceProviders(): void
{
    $addonProviders = [
        // ... existing addons
        'ai-connection-addon' => \Addons\AiConnectionAddon\AddonServiceProvider::class,
    ];
    
    // Registration logic...
}
```

### 2. Run Migrations
```bash
php artisan migrate
```

### 3. Seed Default Providers
```bash
php artisan db:seed --class="Addons\AiConnectionAddon\Database\Seeders\DefaultProvidersSeeder"
```

### 4. Add First Connection
Navigate to Admin → AI Connections → Create Connection

## Usage

### For Admins

#### Adding a Connection
1. Go to Admin → AI Connections → Providers
2. Select a provider (OpenAI, Gemini, OpenRouter)
3. Click "Add Connection"
4. Enter:
   - Name (e.g., "OpenAI Production")
   - API Key
   - Model (e.g., gpt-3.5-turbo)
   - Priority (1 for primary)
   - Rate limits (optional)
5. Click "Test Connection"
6. Save

#### Managing Rotation
- Set Priority: 1 for primary, 2 for backup, etc.
- System automatically rotates on rate limit
- View rotation history in Analytics

#### Monitoring
- Dashboard shows usage per connection
- Track costs by feature
- View error logs
- Health status indicators

### For Developers

#### Using in Consumer Addon
```php
use Addons\AiConnectionAddon\App\Services\AiConnectionService;

class YourService {
    protected $aiConnectionService;
    
    public function __construct(AiConnectionService $aiConnectionService) {
        $this->aiConnectionService = $aiConnectionService;
    }
    
    public function doSomething() {
        // Get OpenAI connections
        $connections = $this->aiConnectionService->getAvailableConnections('openai');
        
        // Execute with auto-rotation
        $result = $this->aiConnectionService->execute(
            connectionId: $connections->first()->id,
            prompt: 'Translate this text...',
            options: [
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]
        );
        
        // Result includes: response, tokens_used, cost, connection_used
    }
}
```

## Modules

### admin_ui
Admin interface for managing connections
- Provider list
- Connection CRUD
- Analytics dashboard
- Test connection UI

### api
Public API for consumer addons
- Get connections
- Execute AI calls
- Track usage

### monitoring
Background jobs
- Health checks
- Usage cleanup
- Error tracking

## Configuration

### Module Control
Enable/disable modules in `addon.json`:
```json
"modules": [
    {
        "key": "admin_ui",
        "enabled": true
    },
    {
        "key": "api",
        "enabled": true
    },
    {
        "key": "monitoring",
        "enabled": true
    }
]
```

### Rate Limiting
Configure per connection:
- `rate_limit_per_minute`: Requests per minute
- `rate_limit_per_day`: Requests per day

System tracks usage and auto-rotates when limit reached.

## Migration from Existing Systems

### Migration Command
```bash
php artisan ai-connections:migrate
```

This command:
1. Exports credentials from Multi-Channel addon
2. Exports credentials from AI Trading addon
3. Exports credentials from Translation service
4. Creates connections in ai_connections table
5. Updates foreign keys in consumer tables
6. Verifies data integrity

### Rollback
```bash
php artisan ai-connections:rollback
```

## Development

### Adding New Provider
1. Create adapter: `app/Providers/YourProviderAdapter.php`
2. Implement `AiProviderInterface`
3. Register in `ProviderAdapterFactory`
4. Seed provider in database
5. Test integration

### Testing
```bash
# Unit tests
php artisan test --filter AiConnectionAddon

# Test rotation
php artisan ai-connections:test-rotation

# Test specific connection
php artisan ai-connections:test-connection {id}
```

## Troubleshooting

### Connection Failing
1. Check connection status in admin UI
2. View error logs in Analytics
3. Test connection manually
4. Verify API key is valid
5. Check rate limits

### Rotation Not Working
1. Verify backup connections exist
2. Check priority settings
3. Review rate limit configuration
4. Check error threshold

### High Costs
1. View Analytics → Usage by Feature
2. Check which features using most tokens
3. Review connection settings (temperature, max_tokens)
4. Consider cheaper models

## Security

### Credential Encryption
All credentials encrypted using Laravel's encryption:
```php
$connection->credentials = encrypt(json_encode($credentials));
```

### Access Control
- Admin routes protected by `admin` middleware
- API routes can be protected by `auth:sanctum` or custom auth

### Audit Logging
All connection usage logged in `ai_connection_usage` table.

## Support

### Logs
- Application logs: `storage/logs/laravel.log`
- Connection errors: Admin → Analytics → Errors
- Usage history: Admin → Analytics → Usage

### Commands
```bash
# Monitor connection health
php artisan ai-connections:monitor

# Cleanup old usage logs (30+ days)
php artisan ai-connections:cleanup-usage

# View connection stats
php artisan ai-connections:stats
```

## Roadmap

### Version 1.0 (Current)
- ✅ Basic provider management
- ✅ Connection CRUD
- ✅ Usage tracking
- ✅ Connection rotation
- ✅ Admin UI

### Version 1.1
- [ ] Advanced analytics
- [ ] Cost alerts
- [ ] Connection pools
- [ ] Multi-region support

### Version 1.2
- [ ] AI provider marketplace
- [ ] Community providers
- [ ] A/B testing support
- [ ] Performance optimization

## License
Proprietary - AlgoExpertHub Platform

## Credits
Developed as part of the AlgoExpertHub AI infrastructure refactoring.

---

**Version**: 1.0.0  
**Status**: Active Development  
**Last Updated**: 2025-12-03

