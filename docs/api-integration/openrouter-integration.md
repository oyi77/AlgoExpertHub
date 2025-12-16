# OpenRouter Integration

Complete guide to OpenRouter AI integration for signal parsing and market analysis.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Signal Parsing Integration](#signal-parsing-integration)
- [Market Analysis Integration](#market-analysis-integration)
- [API Reference](#api-reference)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

---

## Overview

**OpenRouter Integration Addon** provides a unified AI gateway to access 400+ AI models from providers like OpenAI, Anthropic, Google, Meta, and more. The addon integrates with:

- **Multi-Channel Signal Addon**: AI-powered signal parsing from channel messages
- **Trading Execution Engine Addon**: AI market analysis for trade confirmation

### What is OpenRouter?

[OpenRouter](https://openrouter.ai/) is a unified API that provides access to multiple AI models through a single interface. Instead of managing multiple API keys and integrations, you can use OpenRouter to access:

- OpenAI (GPT-4, GPT-3.5, etc.)
- Anthropic (Claude 3.5 Sonnet, Claude 3 Opus, etc.)
- Google (Gemini Pro, PaLM, etc.)
- Meta (Llama 2, Llama 3, etc.)
- And 400+ other models

### Benefits

1. **Unified Interface**: One API key for multiple providers
2. **Model Comparison**: Easily switch between models
3. **Cost Optimization**: Choose models based on cost/performance
4. **Fallback Support**: Automatic fallback if one model fails
5. **Usage Analytics**: Track usage across all models

---

## Features

### Core Features

- ✅ **400+ AI Models**: Access to all major AI providers
- ✅ **Model Management**: Sync and manage available models
- ✅ **Configuration Management**: Multiple configurations with priorities
- ✅ **Signal Parsing**: AI-powered parsing for Multi-Channel Addon
- ✅ **Market Analysis**: AI market confirmation for Execution Engine
- ✅ **Encrypted Storage**: API keys stored encrypted in database
- ✅ **Priority System**: Multiple configurations with priority ordering
- ✅ **Usage Tracking**: Track API usage and costs

### Integration Points

1. **Signal Parsing** (Multi-Channel Addon)
   - Parse trading signals from Telegram/API messages
   - Extract currency pair, direction, prices, SL/TP
   - Confidence scoring for parsed data

2. **Market Analysis** (Execution Engine)
   - Analyze market conditions before trade execution
   - Confirm signal validity based on market data
   - Provide reasoning for trade decisions

---

## Installation

### Prerequisites

- Laravel 9.x application
- PHP 8.0.2 or higher
- AlgoExpertHub Trading Signal Platform
- Multi-Channel Signal Addon (for parsing)
- Trading Execution Engine Addon (for analysis)
- OpenRouter API account

### Step 1: Get OpenRouter API Key

1. Visit [OpenRouter](https://openrouter.ai/)
2. Sign up or log in
3. Navigate to **Keys** section
4. Generate API key (starts with `sk-or-v1-`)
5. Copy the API key

### Step 2: Configure Environment

Add to `.env`:

```env
# OpenRouter Configuration
OPENROUTER_API_KEY=sk-or-v1-your-actual-key-here
OPENROUTER_SITE_URL=https://yourdomain.com
OPENROUTER_SITE_NAME=AlgoExpertHub
```

### Step 3: Run Migrations

```bash
cd main
php artisan migrate
```

This creates:
- `openrouter_configurations` table
- `openrouter_models` table

### Step 4: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Step 5: Sync Models

1. Log in to admin panel
2. Navigate to **OpenRouter > Models**
3. Click **Sync Models**
4. Wait for sync (fetches 400+ models)

### Step 6: Create Configuration

1. Navigate to **OpenRouter > Configurations**
2. Click **Create Configuration**
3. Fill in:
   - **Name**: My Config
   - **API Key**: Your OpenRouter API key
   - **Model**: Select model (e.g., `openai/gpt-4o`)
   - **Temperature**: 0.3 (for consistent parsing)
   - **Max Tokens**: 500
   - **Enable for Parsing**: ✓ (if using Multi-Channel)
   - **Enable for Analysis**: ✓ (if using Execution Engine)
4. Click **Create**

---

## Configuration

### Configuration Fields

| Field | Type | Description |
|-------|------|-------------|
| Name | string | Configuration name (for identification) |
| Model | select | AI model to use (from synced models) |
| API Key | text | OpenRouter API key (encrypted) |
| Temperature | decimal | 0-2, controls randomness (0.3 recommended) |
| Max Tokens | integer | Maximum tokens per request |
| Timeout | integer | Request timeout in seconds |
| Priority | integer | Higher priority = preferred (1-100) |
| Use for Parsing | boolean | Enable for signal parsing |
| Use for Analysis | boolean | Enable for market analysis |
| Enabled | boolean | Enable/disable configuration |

### Recommended Settings

**For Signal Parsing**:
- Temperature: `0.3` (consistent, accurate parsing)
- Max Tokens: `500` (sufficient for signal data)
- Priority: `50` (default)

**For Market Analysis**:
- Temperature: `0.7` (more creative analysis)
- Max Tokens: `1000` (detailed reasoning)
- Priority: `50` (default)

### Model Selection

**Best Models for Parsing**:
- `openai/gpt-4o` - Highest accuracy
- `anthropic/claude-3-5-sonnet` - Balanced performance
- `google/gemini-pro-1.5` - Fast and cost-effective

**Best Models for Analysis**:
- `openai/gpt-4o` - Best reasoning
- `anthropic/claude-3-opus` - Deep analysis
- `google/gemini-pro-1.5` - Cost-effective

---

## Usage

### Signal Parsing (Multi-Channel Addon)

OpenRouter automatically integrates with Multi-Channel Addon for AI parsing.

**Flow**:
1. Channel message received (Telegram/API)
2. `ProcessChannelMessage` job dispatched
3. AI parser selected (OpenRouter if enabled)
4. Message sent to OpenRouter API
5. Response parsed and signal created
6. Confidence score calculated

**Configuration**:
- Enable "Use for Parsing" in OpenRouter configuration
- Set appropriate priority
- Multi-Channel Addon will automatically use OpenRouter

**Example Message**:
```
EUR/USD BUY 1.1000 SL 1.0950 TP 1.1100
```

**Parsed Result**:
```json
{
  "currency_pair": "EUR/USD",
  "direction": "buy",
  "open_price": 1.1000,
  "stop_loss": 1.0950,
  "take_profit": 1.1100,
  "confidence": 95
}
```

---

### Market Analysis (Execution Engine)

OpenRouter provides AI market analysis before trade execution.

**Flow**:
1. Signal published
2. Execution connection has AI analysis enabled
3. Market data gathered (price, volume, indicators)
4. Analysis request sent to OpenRouter
5. AI provides market confirmation
6. Trade executed if analysis positive

**Enable in Execution Connection**:
```json
{
  "enable_ai_market_analysis": true,
  "skip_on_manual_review": true,
  "analysis_confidence_threshold": 70
}
```

**Analysis Result**:
```json
{
  "should_execute": true,
  "confidence": 85,
  "reasoning": "Market conditions favorable. Strong support at entry level. Low volatility indicates stable trend.",
  "risk_level": "medium"
}
```

---

## API Reference

### OpenRouterService

**Location**: `Addons\OpenRouterIntegration\App\Services\OpenRouterService`

**Methods**:

#### sendRequest()

Send request to OpenRouter API.

```php
use Addons\OpenRouterIntegration\App\DTOs\OpenRouterRequest;
use Addons\OpenRouterIntegration\App\Services\OpenRouterService;

$service = app(OpenRouterService::class);

$request = OpenRouterRequest::fromConfig($config, [
    'messages' => [
        ['role' => 'user', 'content' => 'Parse this signal: EUR/USD BUY 1.1000']
    ]
]);

$response = $service->sendRequest($request);

if ($response->success) {
    $content = $response->content;
    // Process content
}
```

#### fetchAvailableModels()

Sync models from OpenRouter API.

```php
$models = $service->fetchAvailableModels();
// Returns Collection of OpenRouterModel
```

#### testConnection()

Test API connection.

```php
$config = OpenRouterConfiguration::find(1);
$isValid = $service->testConnection($config);
```

---

### OpenRouterSignalParser

**Location**: `Addons\OpenRouterIntegration\App\Services\OpenRouterSignalParser`

Implements `AiProviderInterface` for Multi-Channel Addon integration.

**Methods**:

#### parse()

Parse message using OpenRouter.

```php
use Addons\OpenRouterIntegration\App\Services\OpenRouterSignalParser;

$parser = app(OpenRouterSignalParser::class);
$result = $parser->parse('EUR/USD BUY 1.1000', $config);
```

---

### OpenRouterMarketAnalyzer

**Location**: `Addons\OpenRouterIntegration\App\Services\OpenRouterMarketAnalyzer`

Provides market analysis for Execution Engine.

**Methods**:

#### analyze()

Analyze market conditions.

```php
use Addons\OpenRouterIntegration\App\Services\OpenRouterMarketAnalyzer;

$analyzer = app(OpenRouterMarketAnalyzer::class);
$result = $analyzer->analyze($signal, $config);

if ($result->shouldExecute) {
    // Execute trade
}
```

#### quickAnalyze()

Quick market analysis.

```php
$result = $analyzer->quickAnalyze($signal);
```

---

## Best Practices

### 1. Model Selection

- **Parsing**: Use fast, accurate models (GPT-4o, Claude 3.5 Sonnet)
- **Analysis**: Use reasoning-focused models (GPT-4o, Claude 3 Opus)
- **Testing**: Use cheaper models (GPT-3.5, Gemini Pro)

### 2. Temperature Settings

- **Parsing**: Low temperature (0.1-0.3) for consistency
- **Analysis**: Medium temperature (0.5-0.7) for creativity
- **Reasoning**: Higher temperature (0.7-1.0) for exploration

### 3. Token Limits

- **Parsing**: 300-500 tokens (sufficient for signal data)
- **Analysis**: 800-1500 tokens (detailed reasoning)
- **Long Analysis**: 2000+ tokens (comprehensive reports)

### 4. Cost Management

- Monitor usage at [OpenRouter Activity](https://openrouter.ai/activity)
- Set spending limits on OpenRouter account
- Use cheaper models for high-volume operations
- Cache responses when possible

### 5. Error Handling

- Implement retry logic for API failures
- Use fallback configurations
- Log all API errors
- Monitor error rates

### 6. Security

- ✅ Store API keys encrypted
- ✅ Never commit `.env` to version control
- ✅ Rotate API keys periodically
- ✅ Enable 2FA on OpenRouter account
- ✅ Monitor API usage for anomalies

---

## Troubleshooting

### API Key Issues

**Error**: "Configuration not found or invalid"

**Solutions**:
1. Verify API key in `.env`
2. Run `php artisan config:clear`
3. Test API key:
   ```bash
   curl -H "Authorization: Bearer sk-or-v1-your-key" \
        https://openrouter.ai/api/v1/models
   ```

### Models Not Syncing

**Error**: "No models synced"

**Solutions**:
1. Check internet connectivity
2. Verify API key has access
3. Check logs: `storage/logs/laravel.log`
4. Try manual sync again

### Parsing Not Working

**Checklist**:
- [ ] OpenRouter configuration created and enabled
- [ ] "Use for Parsing" enabled
- [ ] Priority set
- [ ] Multi-Channel Addon active
- [ ] API key valid
- [ ] Queue worker running

**Start Queue Worker**:
```bash
php artisan queue:work --tries=3
```

### Market Analysis Not Applied

**Checklist**:
- [ ] OpenRouter configuration created and enabled
- [ ] "Use for Analysis" enabled
- [ ] Execution connection has `enable_ai_market_analysis: true`
- [ ] Trading Execution Engine addon active
- [ ] Signal has required data

### High API Costs

**Solutions**:
1. Use cheaper models for testing
2. Reduce token limits
3. Implement caching
4. Set spending limits
5. Monitor usage regularly

### Slow Response Times

**Solutions**:
1. Use faster models (GPT-3.5, Gemini Pro)
2. Reduce max tokens
3. Implement timeout settings
4. Use async processing
5. Cache common requests

---

## Advanced Usage

### Multiple Configurations

Create multiple configurations for different use cases:

1. **Parsing Config**: Fast, accurate model (GPT-4o)
2. **Analysis Config**: Reasoning-focused model (Claude 3 Opus)
3. **Fallback Config**: Cheaper model (GPT-3.5)

Set priorities to control selection order.

### Custom Prompts

Modify prompts in parser/analyzer services:

```php
// In OpenRouterSignalParser
$prompt = "Parse this trading signal and extract: currency pair, direction, entry price, stop loss, take profit. Return JSON.";
```

### Response Caching

Cache API responses to reduce costs:

```php
$cacheKey = 'openrouter_parsing_' . md5($message);
$result = Cache::remember($cacheKey, 3600, function() use ($message) {
    return $parser->parse($message);
});
```

---

## Monitoring

### Usage Tracking

Monitor API usage:
- OpenRouter Dashboard: [Activity](https://openrouter.ai/activity)
- Application Logs: `storage/logs/laravel.log`
- Database: `openrouter_configurations` table

### Metrics to Monitor

- API request count
- Success/failure rate
- Average response time
- Token usage
- Cost per request
- Error types

---

## Support

- **OpenRouter Docs**: [docs.openrouter.ai](https://docs.openrouter.ai)
- **OpenRouter Discord**: [discord.gg/openrouter](https://discord.gg/openrouter)
- **Platform Support**: Check platform documentation

---

**Last Updated**: 2025-12-02
