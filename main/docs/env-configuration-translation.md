# Environment Configuration for Language Translation

## Required Environment Variables

Add these variables to your `.env` file to enable the auto-translation feature:

```env
# OpenAI API Key for auto-translation feature
# Get your API key from: https://platform.openai.com/api-keys
OPENAI_API_KEY=sk-your-api-key-here

# OpenAI Model to use for translations (optional)
# Options: 
#   - gpt-3.5-turbo (default, cheaper, faster, good quality)
#   - gpt-4 (better quality, more expensive, slower)
# Default: gpt-3.5-turbo
OPENAI_MODEL=gpt-3.5-turbo
```

## Getting Your OpenAI API Key

1. Go to https://platform.openai.com/
2. Sign up or log in
3. Navigate to API Keys section: https://platform.openai.com/api-keys
4. Click "Create new secret key"
5. Copy the key (starts with `sk-`)
6. Add it to your `.env` file as `OPENAI_API_KEY`

**Important**: Never commit your API key to version control!

## API Key Security

### Development
```env
OPENAI_API_KEY=sk-your-development-key
```

### Production
- Use environment-specific keys
- Set up billing limits in OpenAI dashboard
- Monitor usage regularly
- Consider using separate keys for different environments

## Cost Estimates

### Per Language Translation
- **GPT-3.5-turbo**: $0.50 - $2.00
- **GPT-4**: $5.00 - $15.00

### Factors Affecting Cost
- Number of translation keys (~600 in default English)
- Length of text being translated
- Model selected (GPT-4 is ~20x more expensive)

### Monthly Cost Example
If you add 5 new languages per month:
- GPT-3.5-turbo: $2.50 - $10.00/month
- GPT-4: $25 - $75/month

## Queue Worker Configuration

Auto-translation requires a queue worker to be running:

### Development
```bash
php artisan queue:work --tries=1 --timeout=600
```

### Production (Supervisor)
Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=1 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## Optional Configuration

### Custom Timeout (if translations take too long)
Modify `main/app/Jobs/TranslateLanguageJob.php`:
```php
public $timeout = 1200; // 20 minutes instead of 10
```

### Custom Rate Limiting (if hitting API limits)
Modify `main/app/Services/TranslationService.php` in `translateBatch()`:
```php
usleep(200000); // 200ms delay instead of 100ms
```

### Different Model per Environment
```env
# Development - cheaper, faster
OPENAI_MODEL=gpt-3.5-turbo

# Production - better quality
OPENAI_MODEL=gpt-4
```

## Verifying Configuration

### Check if API key is set:
```bash
php artisan tinker
>>> config('services.openai.key')
```

Should output your API key (not null).

### Test OpenAI connection:
```bash
php artisan tinker
>>> $response = \Illuminate\Support\Facades\Http::withHeaders([
...     'Authorization' => 'Bearer ' . config('services.openai.key'),
...     'Content-Type' => 'application/json',
... ])->timeout(10)->post('https://api.openai.com/v1/chat/completions', [
...     'model' => 'gpt-3.5-turbo',
...     'messages' => [['role' => 'user', 'content' => 'Test']],
...     'max_tokens' => 5,
... ]);
>>> $response->successful()
```

Should return `true`.

## Troubleshooting

### "OpenAI API key not configured"
- Check if `OPENAI_API_KEY` is in `.env`
- Run `php artisan config:clear` to clear cache
- Restart queue worker

### "Authentication failed" or 401 errors
- Verify API key is correct
- Check if key has been revoked in OpenAI dashboard
- Ensure no extra spaces in `.env` value

### Translations not processing
- Check if queue worker is running: `ps aux | grep queue:work`
- Check queue status: `php artisan queue:failed`
- Check logs: `tail -f storage/logs/laravel.log`

### Rate limit errors
- Increase delay in `TranslationService::translateBatch()`
- Check OpenAI dashboard for rate limits
- Consider upgrading OpenAI tier

## Monitoring

### Check translation logs:
```bash
tail -f storage/logs/laravel.log | grep -i translation
```

### Check OpenAI usage:
- Go to https://platform.openai.com/usage
- Monitor costs and usage
- Set up billing alerts

### Check failed jobs:
```bash
php artisan queue:failed
```

## Best Practices

1. **Set billing limits** in OpenAI dashboard
2. **Monitor costs** regularly
3. **Use GPT-3.5-turbo** for most translations
4. **Review AI translations** before going live
5. **Keep queue worker running** in production
6. **Rotate API keys** periodically for security
7. **Use environment-specific keys** (dev vs prod)
8. **Set up alerts** for unusual API usage

## References

- OpenAI API Documentation: https://platform.openai.com/docs/
- OpenAI Pricing: https://openai.com/pricing
- Laravel Queues: https://laravel.com/docs/9.x/queues
- Supervisor: http://supervisord.org/

