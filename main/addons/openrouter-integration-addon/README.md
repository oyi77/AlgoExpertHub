# OpenRouter Integration Addon

Unified AI gateway for signal parsing and market analysis powered by OpenRouter API.

## Overview

The OpenRouter Integration Addon provides AI capabilities for the AlgoExpertHub Trading Signal Platform through OpenRouter's unified API, giving access to 400+ AI models from various providers (OpenAI, Anthropic, Google, Meta, and more).

**Key Features:**
- **Signal Parsing**: Automatically parse trading signals from channel messages using AI
- **Market Analysis**: Validate signals against market conditions before execution
- **Dynamic Model Support**: Choose from 400+ AI models
- **Multi-Channel Integration**: Seamlessly integrates with Multi-Channel Signal Addon
- **Execution Engine Integration**: AI-powered market confirmation before trade execution

## Installation

### 1. Addon Registration

The addon is automatically registered in `App\Providers\AppServiceProvider`:

```php
'openrouter-integration-addon' => \Addons\OpenRouterIntegration\AddonServiceProvider::class,
```

### 2. Run Migrations

```bash
php artisan migrate
```

This creates:
- `openrouter_configurations` - Configuration management
- `openrouter_models` - Available models cache

### 3. Environment Configuration (Optional)

Optionally add default site information to your `.env` file:

```env
# Optional: Default values for all configurations
OPENROUTER_SITE_URL=https://yourdomain.com
OPENROUTER_SITE_NAME=YourAppName
```

**Note**: API keys are configured per-configuration through the admin panel, not in `.env`.

## Configuration

### Admin Interface

Access the admin panel at `/admin/openrouter/configurations`.

#### Create Configuration

1. Navigate to **OpenRouter > Configurations**
2. Click **Create Configuration**
3. Fill in the form:
   - **Name**: Descriptive name for the configuration
   - **API Key**: Your OpenRouter API key (get from [OpenRouter Keys](https://openrouter.ai/keys))
   - **Model**: Select from synced models
   - **Site URL**: Optional, defaults to your APP_URL
   - **Site Name**: Optional, defaults to your APP_NAME
   - **Temperature**: 0.0 (deterministic) to 2.0 (creative)
   - **Max Tokens**: Maximum response length
   - **Timeout**: API request timeout (seconds)
   - **Priority**: Higher priority configs are used first
   - **Enable for Signal Parsing**: Use for parsing channel messages
   - **Enable for Market Analysis**: Use for signal validation

**Important**: Each configuration has its own API key. You can use different API keys for different purposes or share one key across multiple configurations.

#### Sync Models

Before creating your first configuration:

1. Create at least one configuration with a valid API key (see above)
2. Navigate to **OpenRouter > Models**
3. Click **Sync Models**
4. Wait for models to be fetched from OpenRouter API (uses the first active configuration)

**Note**: Model sync requires at least one active configuration with a valid API key.

**Recommended Models:**
- **Signal Parsing**: `openai/gpt-4o`, `anthropic/claude-3-5-sonnet`
- **Market Analysis**: `openai/gpt-4o`, `google/gemini-pro-1.5`

## Usage

### 1. Signal Parsing (Multi-Channel Addon Integration)

The addon automatically registers as an AI provider for the Multi-Channel Signal Addon.

**How it works:**
1. Admin creates OpenRouter configuration
2. Enables "Use for Parsing"
3. Multi-Channel Addon automatically uses OpenRouter for AI parsing
4. Parsed signals are created as drafts for review

**Message Processing:**
```
Telegram Channel → ProcessChannelMessage Job → ParsingPipeline → 
OpenRouterSignalParser → Draft Signal
```

**Example:**
A channel message like:
```
🔥 BTC/USDT SIGNAL 🔥
Direction: LONG
Entry: 45000
Stop Loss: 44000
Take Profit: 47000
Timeframe: 4H
```

Is automatically parsed to:
```json
{
  "currency_pair": "BTC/USDT",
  "direction": "long",
  "open_price": 45000,
  "stop_loss": 44000,
  "take_profit": 47000,
  "timeframe": "4H",
  "confidence": 95
}
```

### 2. Market Analysis (Execution Engine Integration)

Validate signals against market conditions before execution.

**Enable in Execution Connection:**

In the Execution Engine addon, add to connection settings:

```json
{
  "enable_ai_market_analysis": true,
  "skip_on_manual_review": true
}
```

**How it works:**
1. Signal is published
2. ExecuteSignalJob is dispatched
3. Market analysis performed (if enabled)
4. AI evaluates signal against market data
5. Recommendation applied:
   - **Accept**: Execute signal normally
   - **Reject**: Skip execution, log reason
   - **Size Down**: Reduce position size by 50%
   - **Manual Review**: Skip (configurable) or proceed

**Analysis Flow:**
```
Signal Published → ExecuteSignalJob → OpenRouterMarketAnalyzer → 
Market Context → AI Analysis → Recommendation → Execute or Skip
```

**Example Recommendation:**
```json
{
  "alignment": "aligned",
  "risk_score": 25,
  "safety_score": 75,
  "recommendation": "accept",
  "reasoning": "Signal direction matches upward trend, RSI not overbought"
}
```

## API Reference

### OpenRouterService

Core service for API interaction.

```php
use Addons\OpenRouterIntegration\App\Services\OpenRouterService;

$service = app(OpenRouterService::class);

// Send request
$request = new OpenRouterRequest($model, $messages, $temperature, $maxTokens);
$response = $service->sendRequest($request);

// Sync models
$models = $service->fetchAvailableModels();

// Test connection
$success = $service->testConnection($config);
```

### OpenRouterSignalParser

Implements `AiProviderInterface` for Multi-Channel Addon.

```php
use Addons\OpenRouterIntegration\App\Services\OpenRouterSignalParser;

$parser = app(OpenRouterSignalParser::class);

// Parse message
$result = $parser->parse($message, $aiConfig);
// Returns: ['currency_pair' => '...', 'direction' => '...', ...]

// Test connection
$success = $parser->testConnection($aiConfig);
```

### OpenRouterMarketAnalyzer

Analyzes signals against market conditions.

```php
use Addons\OpenRouterIntegration\App\Services\OpenRouterMarketAnalyzer;

$analyzer = app(OpenRouterMarketAnalyzer::class);

// Analyze signal
$result = $analyzer->analyzeSignal($signal, $marketData, $config);

// Check recommendations
if ($result->shouldReject()) {
    // Skip execution
}

if ($result->shouldSizeDown()) {
    // Reduce position size
}
```

## Advanced Configuration

### Custom Prompts

Prompts are defined in service classes. To customize:

1. Create custom parser service extending `OpenRouterSignalParser`
2. Override `buildSignalParsingPrompt()` method
3. Register custom parser in service provider

### Multi-Model Strategy

Configure multiple OpenRouter configurations with different models:

- **High Priority (90)**: GPT-4o for critical signals
- **Medium Priority (50)**: Claude for general parsing
- **Low Priority (10)**: GPT-3.5 Turbo as fallback

The system will attempt models in priority order.

### Rate Limiting

OpenRouter has rate limits based on your plan. Monitor usage at [OpenRouter Dashboard](https://openrouter.ai/activity).

**Best Practices:**
- Use lower temperature (0.1-0.3) for signal parsing
- Set appropriate timeouts (15-30s)
- Cache model lists (synced once per hour)
- Use cheaper models for testing

## Troubleshooting

### Models Not Appearing

**Solution**: Sync models from OpenRouter API:
1. Go to **OpenRouter > Models**
2. Click **Sync Models**
3. Verify API key is valid

### Connection Test Fails

**Possible Causes:**
- Invalid API key
- Model not available
- Network/firewall issues
- OpenRouter API downtime

**Solution**:
1. Verify API key in `.env`
2. Check model availability
3. Test API directly: `curl https://openrouter.ai/api/v1/models`

### Parsing Not Working

**Checklist:**
- OpenRouter configuration created
- "Use for Parsing" enabled
- Priority set correctly
- Multi-Channel Addon active

**Logs**: Check `storage/logs/laravel.log` for parsing errors.

### Market Analysis Not Applied

**Checklist:**
- OpenRouter configuration created
- "Use for Analysis" enabled
- Execution connection has `enable_ai_market_analysis: true`
- Trading Execution Engine addon active

## Security

### API Key Storage

API keys are stored encrypted in the database using Laravel's `Crypt` facade. Each configuration has its own API key.

```php
// Automatic encryption on save
$config->api_key = 'sk-or-v1-...'; // Stored encrypted in database

// Automatic decryption on retrieval
$apiKey = $config->getDecryptedApiKey(); // Returns plain text
```

**Security Features:**
- All API keys encrypted at rest in database
- Keys never exposed in logs or responses
- Separate keys per configuration (optional)
- No keys stored in `.env` or version control

### Best Practices

1. **Use separate API keys** for different environments (dev, staging, production)
2. **Rotate API keys** periodically via admin panel
3. **Monitor usage** on OpenRouter dashboard
4. **Set spending limits** on your OpenRouter account
5. **Enable 2FA** on OpenRouter account
6. **Delete unused configurations** to prevent unauthorized API usage

## Performance

### Caching

Models are cached for 1 hour (configurable in `config/openrouter.php`):

```php
'cache_models_for' => 3600, // 1 hour
```

### Timeouts

Default timeout: 30 seconds. Adjust per configuration based on model:

- Fast models (GPT-3.5): 15-20s
- Large models (GPT-4, Claude): 30-45s

### Queue Jobs

Signal parsing and market analysis are processed asynchronously via Laravel queues.

**Run queue worker**:
```bash
php artisan queue:work --queue=high,default
```

## Pricing

OpenRouter pricing varies by model. View current pricing: [OpenRouter Models](https://openrouter.ai/docs/models).

**Cost Estimation** (approximate):
- Signal parsing: $0.001 - $0.01 per message
- Market analysis: $0.01 - $0.05 per signal

**Example Monthly Cost** (1000 signals):
- Parsing: $1 - $10
- Analysis: $10 - $50

## Support

### Documentation

- [OpenRouter Official Docs](https://openrouter.ai/docs)
- [Multi-Channel Signal Addon](../multi-channel-signal-addon/README.md)
- [Trading Execution Engine Addon](../trading-execution-engine-addon/README.md)

### Community

- Platform Support: support@algoexperthub.com
- OpenRouter Discord: [Join](https://discord.gg/openrouter)

## Changelog

### Version 1.0.0 (2025-12-02)

**Initial Release:**
- Signal parsing integration with Multi-Channel Addon
- Market analysis for Execution Engine
- Dynamic model support (400+ models)
- Admin interface for configuration management
- Model sync from OpenRouter API
- API key encryption
- Test connection functionality
- Unit and integration tests

## License

Proprietary - AlgoExpertHub Trading Signal Platform

## Credits

- OpenRouter API: [openrouter.ai](https://openrouter.ai)
- Developed by: AlgoExpertHub Development Team

