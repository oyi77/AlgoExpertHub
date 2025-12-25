# AI Connection Addon - User Guide

## Overview

The **AI Connection Addon** manages all AI provider connections for the platform. Instead of configuring AI credentials separately for each feature, this addon provides centralized management with automatic connection rotation, rate limiting, and usage tracking.

## Table of Contents

1. [What is AI Connection Addon?](#what-is-ai-connection-addon)
2. [Getting Started](#getting-started)
3. [Managing AI Connections](#managing-ai-connections)
4. [Supported AI Providers](#supported-ai-providers)
5. [Usage Analytics](#usage-analytics)
6. [Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)

---

## What is AI Connection Addon?

### Purpose

This addon centralizes AI provider management for all platform features that use AI:
- **Multi-Channel Signal Addon**: AI-powered message parsing
- **Trading Management Addon**: AI market analysis and confirmation
- **OpenRouter Integration**: Access to 400+ AI models

### Benefits

- **Single Source of Truth**: Manage all AI credentials in one place
- **Automatic Rotation**: Distribute load across multiple connections
- **Rate Limiting**: Prevent API limit errors
- **Usage Tracking**: Monitor AI usage and costs
- **High Availability**: Automatic failover when connections fail

---

## Getting Started

### Accessing AI Connections

1. Log in as **Admin**
2. Navigate to **Admin → AI Connections**
3. You'll see the AI Connection dashboard

### Quick Setup (3 Minutes)

1. **Add Your First Connection** - Configure OpenAI, Gemini, or OpenRouter
2. **Test Connection** - Verify it works
3. **Set Priority** - Higher priority connections used first
4. **Monitor Usage** - Track AI calls and costs

---

## Managing AI Connections

### Adding a Connection

#### OpenAI Connection

1. Click **Add Connection**
2. Fill in the form:
   - **Name**: e.g., "OpenAI Primary"
   - **Provider**: Select "OpenAI"
   - **API Key**: Your OpenAI API key
   - **Organization ID** (optional): Your OpenAI organization
   - **Model**: Default model (e.g., "gpt-4", "gpt-3.5-turbo")
   - **Rate Limit**: Requests per minute (e.g., 60)
   - **Priority**: 1-10 (higher = preferred)
3. Click **Test Connection**
4. Click **Save**

**Where to get OpenAI API Key:**
1. Go to https://platform.openai.com/api-keys
2. Click "Create new secret key"
3. Copy the key (save it securely!)
4. Paste into AI Connection form

#### Google Gemini Connection

1. Click **Add Connection**
2. Fill in the form:
   - **Name**: e.g., "Gemini Pro"
   - **Provider**: Select "Gemini"
   - **API Key**: Your Google AI API key
   - **Model**: Default model (e.g., "gemini-pro", "gemini-pro-vision")
   - **Rate Limit**: Requests per minute (e.g., 60)
   - **Priority**: 1-10
3. Click **Test Connection**
4. Click **Save**

**Where to get Gemini API Key:**
1. Go to https://makersuite.google.com/app/apikey
2. Click "Create API key"
3. Copy the key
4. Paste into AI Connection form

#### OpenRouter Connection

1. Click **Add Connection**
2. Fill in the form:
   - **Name**: e.g., "OpenRouter Multi-Model"
   - **Provider**: Select "OpenRouter"
   - **API Key**: Your OpenRouter API key
   - **Model**: Default model (e.g., "anthropic/claude-3-opus", "meta-llama/llama-3-70b")
   - **Rate Limit**: Requests per minute (e.g., 100)
   - **Priority**: 1-10
3. Click **Test Connection**
4. Click **Save**

**Where to get OpenRouter API Key:**
1. Go to https://openrouter.ai/keys
2. Create an account if needed
3. Click "Create Key"
4. Copy the key
5. Paste into AI Connection form

### Connection Settings

#### Name
- Descriptive name for the connection
- Helps you identify connections quickly
- Example: "OpenAI Primary", "Gemini Backup", "OpenRouter Budget"

#### Provider
- **OpenAI**: GPT-4, GPT-3.5-turbo, GPT-4-turbo
- **Gemini**: Gemini Pro, Gemini Pro Vision
- **OpenRouter**: 400+ models from various providers

#### Model
- Default model for this connection
- Can be overridden by consumer features
- Choose based on task complexity:
  - **Simple tasks**: GPT-3.5-turbo, Gemini Pro (cheaper)
  - **Complex tasks**: GPT-4, Claude 3 Opus (more accurate)

#### Rate Limit
- Maximum requests per minute
- Based on your API plan
- Set conservatively to avoid errors
- Example: OpenAI free tier = 3 RPM, paid tier = 60+ RPM

#### Priority
- 1-10 scale (10 = highest priority)
- Higher priority connections used first
- Use for:
  - **10**: Primary production connection
  - **5**: Backup connection
  - **1**: Fallback/budget connection

### Editing Connections

1. Click **Edit** on any connection
2. Update settings
3. Click **Test Connection** to verify changes
4. Click **Save**

### Deleting Connections

1. Click **Delete** on connection
2. Confirm deletion
3. Connection removed (cannot be undone)

**Warning**: Deleting a connection may affect features using it!

### Testing Connections

Click **Test Connection** to:
- Verify API key is valid
- Check model availability
- Measure response time
- Update health status

Test results show:
- ✅ **Success**: Connection working
- ⚠️ **Warning**: Slow response
- ❌ **Error**: Connection failed

---

## Supported AI Providers

### OpenAI

**Models Available:**
- `gpt-4` - Most capable, best for complex tasks
- `gpt-4-turbo` - Faster GPT-4, lower cost
- `gpt-3.5-turbo` - Fast, affordable, good for simple tasks
- `gpt-3.5-turbo-16k` - Larger context window

**Best For:**
- Signal parsing (complex messages)
- Market analysis
- Natural language understanding

**Pricing** (as of 2024):
- GPT-4: $0.03/1K tokens (input), $0.06/1K tokens (output)
- GPT-3.5-turbo: $0.0015/1K tokens (input), $0.002/1K tokens (output)

### Google Gemini

**Models Available:**
- `gemini-pro` - Text generation and analysis
- `gemini-pro-vision` - Text + image understanding

**Best For:**
- Cost-effective AI analysis
- Multi-modal tasks (text + images)
- Fast responses

**Pricing** (as of 2024):
- Gemini Pro: Free tier available, then $0.00025/1K characters

### OpenRouter

**Models Available:**
- 400+ models from multiple providers
- Anthropic Claude, Meta Llama, Mistral, and more
- Unified API for all models

**Best For:**
- Access to multiple AI providers
- Model comparison and testing
- Fallback when primary provider unavailable

**Pricing**:
- Varies by model
- Pay-as-you-go
- Credits system

---

## Usage Analytics

### Viewing Usage

Go to **Admin → AI Connections → Analytics**

### Metrics Available

#### Overview
- **Total API Calls**: All-time AI requests
- **Total Tokens**: Input + output tokens used
- **Estimated Cost**: Based on provider pricing
- **Average Response Time**: Speed of AI responses

#### By Consumer
See which features use AI most:
- Multi-Channel Signal Addon
- Trading Management (AI Analysis)
- OpenRouter Integration
- Other features

#### By Provider
Compare usage across providers:
- OpenAI usage and cost
- Gemini usage and cost
- OpenRouter usage and cost

#### By Model
See which models are used:
- GPT-4 vs GPT-3.5-turbo
- Gemini Pro
- Claude, Llama, etc.

#### By Time
- Daily usage trends
- Peak usage hours
- Monthly costs

### Exporting Data

1. Click **Export** on analytics page
2. Choose format (CSV, Excel, PDF)
3. Select date range
4. Download report

---

## Best Practices

### Connection Strategy

#### Multiple Connections
- **Primary**: High-quality, reliable (e.g., OpenAI GPT-4)
- **Backup**: Alternative provider (e.g., Gemini Pro)
- **Fallback**: Budget option (e.g., GPT-3.5-turbo)

**Benefits:**
- High availability (if one fails, others work)
- Load distribution (avoid rate limits)
- Cost optimization (use cheaper models when possible)

#### Priority Setup
```
Priority 10: OpenAI GPT-4 (primary, best quality)
Priority 7:  Gemini Pro (backup, good quality, cheaper)
Priority 5:  OpenAI GPT-3.5-turbo (fallback, fast, cheap)
Priority 3:  OpenRouter Claude (alternative, high quality)
```

### Rate Limiting

#### Set Conservative Limits
- Start with 50% of your API plan limit
- Monitor usage and adjust
- Example: 60 RPM plan → set 30 RPM limit

#### Why Conservative?
- Prevents hitting hard limits
- Allows buffer for spikes
- Avoids API errors

### Cost Optimization

#### Use Appropriate Models
- **Simple tasks** (signal parsing): GPT-3.5-turbo
- **Complex tasks** (market analysis): GPT-4
- **Bulk processing**: Gemini Pro (cheaper)

#### Monitor Costs
- Check analytics weekly
- Set budget alerts
- Optimize prompts (shorter = cheaper)

#### Prompt Optimization
- Be concise and specific
- Avoid unnecessary context
- Reuse responses when possible

### Security

#### API Key Safety
- Never share API keys
- Rotate keys periodically (every 3-6 months)
- Use separate keys for production and testing
- Revoke unused keys

#### Access Control
- Only admins can manage connections
- Audit connection changes
- Monitor for unusual usage

---

## Troubleshooting

### Connection Failed

**Problem**: "Connection test failed" error

**Solutions:**
1. **Verify API Key**:
   - Copy-paste carefully (no extra spaces)
   - Check key hasn't expired
   - Verify key is for correct provider

2. **Check API Plan**:
   - Ensure you have active subscription
   - Verify credits available (OpenRouter)
   - Check billing status

3. **Network Issues**:
   - Test internet connection
   - Check firewall settings
   - Verify API endpoint accessible

4. **Provider Status**:
   - Check provider status page
   - OpenAI: https://status.openai.com
   - Google: https://status.cloud.google.com

### Rate Limit Errors

**Problem**: "Rate limit exceeded" messages

**Solutions:**
1. **Reduce Rate Limit**:
   - Lower requests per minute setting
   - Set to 50-70% of plan limit

2. **Add More Connections**:
   - Create additional connections
   - Distribute load across providers

3. **Upgrade API Plan**:
   - Increase rate limits with provider
   - Consider paid tier

### High Costs

**Problem**: AI usage costs too high

**Solutions:**
1. **Use Cheaper Models**:
   - Switch from GPT-4 to GPT-3.5-turbo
   - Use Gemini Pro instead of OpenAI

2. **Optimize Prompts**:
   - Shorter prompts = lower cost
   - Remove unnecessary context
   - Cache responses when possible

3. **Set Usage Limits**:
   - Limit AI calls per day
   - Disable AI for non-critical features
   - Use AI only when needed

### Slow Responses

**Problem**: AI taking too long to respond

**Solutions:**
1. **Use Faster Models**:
   - GPT-3.5-turbo faster than GPT-4
   - Gemini Pro very fast

2. **Check Connection Health**:
   - Test connection
   - Verify network speed
   - Check provider status

3. **Reduce Prompt Size**:
   - Shorter prompts = faster response
   - Remove unnecessary details

### Connection Health Issues

**Problem**: Connection showing "degraded" or "down"

**Solutions:**
1. **Run Health Check**:
   - Click "Test Connection"
   - Review error message
   - Fix identified issues

2. **Check Provider Status**:
   - Provider may be experiencing issues
   - Wait and retry later

3. **Rotate to Backup**:
   - System automatically uses backup connections
   - Verify backup connections healthy

---

## Advanced Features

### Connection Rotation

**How it Works:**
1. System selects highest priority healthy connection
2. If rate limited, rotates to next available connection
3. Distributes load across multiple connections

**Benefits:**
- Prevents rate limit errors
- Maximizes availability
- Optimizes costs

### Health Monitoring

**Automatic Health Checks:**
- Run every 5 minutes
- Test each connection
- Update health status

**Health Statuses:**
- 🟢 **Healthy**: Working normally
- 🟡 **Degraded**: Slow but functional
- 🔴 **Down**: Not responding

**Alerts:**
- Email notification when connection goes down
- Dashboard notification
- Automatic rotation to healthy connections

### Usage Tracking

**What's Tracked:**
- Every AI API call
- Tokens used (input + output)
- Response time
- Success/failure status
- Consumer (which feature called)

**Why Track:**
- Monitor costs
- Identify usage patterns
- Optimize performance
- Debug issues

---

## Integration with Features

### Multi-Channel Signal Addon

**Use Case**: Parse incoming messages to extract trading signals

**How It Works:**
1. Message received from Telegram/API
2. Multi-Channel addon calls AI Connection service
3. AI Connection selects best available connection
4. AI parses message and extracts signal data
5. Usage logged for analytics

### Trading Management (AI Analysis)

**Use Case**: AI-powered market confirmation before trade execution

**How It Works:**
1. Signal published
2. Trading Management calls AI Connection service
3. AI analyzes market conditions
4. AI provides recommendation (execute/skip)
5. Trade executed based on AI decision

### OpenRouter Integration

**Use Case**: Access to 400+ AI models

**How It Works:**
1. User selects model from OpenRouter
2. OpenRouter addon calls AI Connection service
3. AI Connection routes to OpenRouter connection
4. Response returned to user

---

## FAQ

**Q: How many connections should I create?**
A: Minimum 2 (primary + backup), ideally 3-4 for high availability.

**Q: Which provider is best?**
A: Depends on use case. OpenAI GPT-4 for quality, Gemini Pro for cost, OpenRouter for variety.

**Q: Can I use free tier API keys?**
A: Yes, but rate limits are very low. Recommended for testing only.

**Q: How much does AI usage cost?**
A: Varies by provider and model. Check analytics for your actual costs. Typical: $10-100/month depending on usage.

**Q: What happens if all connections fail?**
A: Features requiring AI will fail gracefully and notify you. No trades executed without AI if AI is required.

**Q: Can I use custom AI models?**
A: Yes, via OpenRouter you can access custom models. Self-hosted models not currently supported.

**Q: How do I reduce AI costs?**
A: Use cheaper models (GPT-3.5-turbo, Gemini Pro), optimize prompts, cache responses, limit usage.

---

## Support

### Getting Help

- **Documentation**: This guide
- **Support Tickets**: Submit in platform
- **Community Forum**: Ask questions
- **Provider Support**: Contact AI provider for API issues

### Useful Links

- **OpenAI**: https://platform.openai.com/docs
- **Google Gemini**: https://ai.google.dev/docs
- **OpenRouter**: https://openrouter.ai/docs

---

**Need more help?** Contact support or visit our community forum!
