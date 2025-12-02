# OpenRouter Integration Addon - Installation Guide

## Prerequisites

- Laravel 9.x application running
- PHP 8.0.2 or higher
- AlgoExpertHub Trading Signal Platform (base application)
- Multi-Channel Signal Addon (for signal parsing integration)
- Trading Execution Engine Addon (for market analysis integration)
- OpenRouter API account and API key

## Installation Steps

### 1. Get OpenRouter API Key

1. Visit [OpenRouter](https://openrouter.ai/)
2. Sign up or log in to your account
3. Navigate to **Keys** section
4. Generate a new API key (starts with `sk-or-v1-`)
5. Copy the API key (you'll need it in step 3)

### 2. Verify Addon Installation

The addon should be located at:
```
main/addons/openrouter-integration-addon/
```

Verify the directory structure exists:
```
openrouter-integration-addon/
├── addon.json
├── AddonServiceProvider.php
├── app/
├── config/
├── database/
├── resources/
├── routes/
├── README.md
└── INSTALLATION.md
```

### 3. Configure Environment Variables

Add the following to your `.env` file in the project root:

```env
# OpenRouter Configuration
OPENROUTER_API_KEY=sk-or-v1-your-actual-key-here
OPENROUTER_SITE_URL=https://yourdomain.com
OPENROUTER_SITE_NAME=YourAppName
```

**Important**: Replace the values with your actual credentials.

### 4. Run Database Migrations

Execute the migrations to create required tables:

```bash
cd /home1/algotrad/public_html/main
php artisan migrate
```

This will create:
- `openrouter_configurations` table
- `openrouter_models` table

### 5. Clear Application Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### 6. Verify Addon Registration

Check if the addon is registered in `app/Providers/AppServiceProvider.php`:

```php
'openrouter-integration-addon' => \Addons\OpenRouterIntegration\AddonServiceProvider::class,
```

If not present, add it to the `$addonProviders` array in the `registerAddonServiceProviders()` method.

### 7. Sync Available Models

1. Log in to the admin panel
2. Navigate to **OpenRouter > Models**
3. Click **Sync Models** button
4. Wait for the sync to complete (fetches 400+ models from OpenRouter API)

### 8. Create Your First Configuration

1. Navigate to **OpenRouter > Configurations**
2. Click **Create Configuration**
3. Fill in the form:
   - **Name**: My First Config
   - **API Key**: (your OpenRouter API key)
   - **Model**: Select `openai/gpt-4o` or `anthropic/claude-3-5-sonnet`
   - **Temperature**: 0.3
   - **Max Tokens**: 500
   - **Timeout**: 30
   - **Priority**: 50
   - **Enable for Signal Parsing**: ✓ (if using Multi-Channel Addon)
   - **Enable for Market Analysis**: ✓ (if using Execution Engine)
4. Click **Create Configuration**

### 9. Test Connection

After creating a configuration:
1. Click the **Test Connection** button (plug icon)
2. Wait for the test to complete
3. Verify success message appears

## Integration Configuration

### For Signal Parsing (Multi-Channel Addon)

The integration is automatic once you:
1. Create an OpenRouter configuration
2. Enable "Use for Parsing"
3. Set appropriate priority

The Multi-Channel Addon will automatically use OpenRouter for AI parsing.

### For Market Analysis (Execution Engine Addon)

Enable AI market analysis in Execution Connections:

1. Navigate to **Execution Engine > Connections**
2. Edit a connection or create a new one
3. Add to connection settings (JSON):
   ```json
   {
     "enable_ai_market_analysis": true,
     "skip_on_manual_review": true
   }
   ```
4. Save the connection

## Verification

### Test Signal Parsing

1. Send a test signal to a configured Telegram channel
2. Check **Multi-Channel > Channel Messages**
3. Verify the message was parsed and signal created
4. Check logs: `storage/logs/laravel.log` for parsing details

### Test Market Analysis

1. Publish a signal
2. Verify execution connection has AI analysis enabled
3. Check **Execution Engine > Execution Logs**
4. Look for analysis reasoning in log details

## Troubleshooting

### API Key Issues

**Error**: "Configuration not found or invalid"

**Solution**:
1. Verify API key in `.env` is correct
2. Run `php artisan config:clear`
3. Test API key directly:
   ```bash
   curl -H "Authorization: Bearer sk-or-v1-your-key" \
        https://openrouter.ai/api/v1/models
   ```

### Models Not Syncing

**Error**: "No models synced"

**Solution**:
1. Check internet connectivity
2. Verify API key has access to models endpoint
3. Check logs: `storage/logs/laravel.log`
4. Try manual sync again

### Parsing Not Working

**Checklist**:
- [ ] OpenRouter configuration created and enabled
- [ ] "Use for Parsing" checkbox checked
- [ ] Priority set (higher = preferred)
- [ ] Multi-Channel Addon is active
- [ ] API key is valid
- [ ] Queue worker is running

**Start queue worker**:
```bash
php artisan queue:work --tries=3
```

### Market Analysis Not Applied

**Checklist**:
- [ ] OpenRouter configuration created and enabled
- [ ] "Use for Analysis" checkbox checked
- [ ] Execution connection has `enable_ai_market_analysis: true`
- [ ] Trading Execution Engine addon is active
- [ ] Signal has all required data (pair, direction, prices)

## Post-Installation

### Recommended Models

**For Signal Parsing** (accuracy-focused):
- `openai/gpt-4o` (best accuracy)
- `anthropic/claude-3-5-sonnet` (balanced)
- `google/gemini-pro-1.5` (fast)

**For Market Analysis** (reasoning-focused):
- `openai/gpt-4o` (best reasoning)
- `anthropic/claude-3-opus` (deep analysis)
- `google/gemini-pro-1.5` (cost-effective)

### Cost Management

1. Set spending limits on OpenRouter account
2. Monitor usage at [OpenRouter Activity](https://openrouter.ai/activity)
3. Use cheaper models for testing
4. Implement rate limiting if needed

### Security Best Practices

1. ✅ Keep API key in `.env` file
2. ✅ Never commit `.env` to version control
3. ✅ Rotate API keys periodically
4. ✅ Enable 2FA on OpenRouter account
5. ✅ Monitor API usage regularly

## Support

For issues or questions:
- Platform Support: support@algoexperthub.com
- OpenRouter Support: [OpenRouter Discord](https://discord.gg/openrouter)
- Documentation: See [README.md](README.md)

## Next Steps

- Read the [full documentation](README.md)
- Configure additional models
- Set up signal parsing
- Enable market analysis
- Monitor logs and usage
- Optimize configurations

## Uninstallation (if needed)

To remove the addon:

1. Disable configurations:
   ```sql
   UPDATE openrouter_configurations SET enabled = 0;
   ```

2. Remove from `AppServiceProvider.php`:
   ```php
   // Remove or comment out:
   // 'openrouter-integration-addon' => \Addons\OpenRouterIntegration\AddonServiceProvider::class,
   ```

3. Optionally drop tables:
   ```sql
   DROP TABLE openrouter_configurations;
   DROP TABLE openrouter_models;
   ```

4. Clear cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

---

**Version**: 1.0.0  
**Last Updated**: 2025-12-02  
**Addon Status**: Production Ready

