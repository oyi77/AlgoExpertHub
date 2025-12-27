# Troubleshooting Guide

Common issues and solutions for AlgoExpertHub Trading Signal Platform.

## Table of Contents

- [General Issues](#general-issues)
- [Installation Issues](#installation-issues)
- [Database Issues](#database-issues)
- [Queue & Jobs Issues](#queue--jobs-issues)
- [Payment Gateway Issues](#payment-gateway-issues)
- [Signal Distribution Issues](#signal-distribution-issues)
- [Multi-Channel Addon Issues](#multi-channel-addon-issues)
- [Execution Engine Issues](#execution-engine-issues)
- [Performance Issues](#performance-issues)
- [Security Issues](#security-issues)

---

## General Issues

### Application Returns 500 Error

**Symptoms**: White screen or "500 Internal Server Error"

**Solutions**:

1. **Check Logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Clear Cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

3. **Check Permissions**:
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

4. **Verify .env File**:
   ```bash
   php artisan config:cache
   ```

5. **Check PHP Error Log**:
   ```bash
   tail -f /var/log/php8.0-fpm.log
   ```

### Application Key Not Set

**Error**: `RuntimeException: No application encryption key has been specified`

**Solution**:
```bash
php artisan key:generate
```

### Storage Link Not Working

**Error**: Images/uploads not displaying

**Solution**:
```bash
php artisan storage:link
```

Verify symlink exists:
```bash
ls -la public/storage
```

---

## Installation Issues

### Composer Install Fails

**Error**: Memory limit or dependency conflicts

**Solutions**:

1. **Increase Memory Limit**:
   ```bash
   php -d memory_limit=512M /usr/local/bin/composer install
   ```

2. **Update Composer**:
   ```bash
   composer self-update
   ```

3. **Clear Composer Cache**:
   ```bash
   composer clear-cache
   ```

### Migration Fails

**Error**: SQL errors during migration

**Solutions**:

1. **Check Database Connection**:
   ```bash
   php artisan tinker
   DB::connection()->getPdo();
   ```

2. **Check MySQL Version**:
   ```sql
   SELECT VERSION();
   ```
   Requires MySQL 5.7+ or MariaDB 10.3+

3. **Run Migrations One by One**:
   ```bash
   php artisan migrate:status
   php artisan migrate --path=/database/migrations/2023_02_22_104311_create_admins_table.php
   ```

4. **Check Foreign Key Constraints**:
   ```sql
   SET FOREIGN_KEY_CHECKS=0;
   -- Run migration
   SET FOREIGN_KEY_CHECKS=1;
   ```

### Addon Not Loading

**Error**: Addon routes/features not working

**Solutions**:

1. **Check Addon Status**:
   ```php
   \App\Support\AddonRegistry::active('addon-slug');
   ```

2. **Verify Service Provider**:
   Check `AppServiceProvider::registerAddonServiceProviders()`

3. **Clear Cache**:
   ```bash
   php artisan config:clear
   php artisan route:clear
   ```

4. **Check Addon Manifest**:
   Verify `addon.json` exists and is valid JSON

---

## Database Issues

### Connection Refused

**Error**: `SQLSTATE[HY000] [2002] Connection refused`

**Solutions**:

1. **Check MySQL Service**:
   ```bash
   sudo systemctl status mysql
   sudo systemctl start mysql
   ```

2. **Verify Credentials**:
   ```bash
   mysql -u username -p database_name
   ```

3. **Check .env Configuration**:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Test Connection**:
   ```bash
   php artisan tinker
   DB::connection()->getPdo();
   ```

### Table Doesn't Exist

**Error**: `Base table or view not found`

**Solutions**:

1. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

2. **Check Migration Status**:
   ```bash
   php artisan migrate:status
   ```

3. **Import SQL File**:
   ```bash
   mysql -u username -p database_name < database/sql/database.sql
   ```

### Foreign Key Constraint Fails

**Error**: `Cannot add or update a child row: a foreign key constraint fails`

**Solutions**:

1. **Check Referenced Data Exists**:
   ```sql
   SELECT * FROM parent_table WHERE id = X;
   ```

2. **Disable Foreign Key Checks Temporarily**:
   ```sql
   SET FOREIGN_KEY_CHECKS=0;
   -- Perform operation
   SET FOREIGN_KEY_CHECKS=1;
   ```

3. **Verify Data Integrity**:
   Check orphaned records and clean up

---

## Queue & Jobs Issues

### Jobs Not Processing

**Symptoms**: Jobs stuck in `jobs` table, not executing

**Solutions**:

1. **Check Queue Worker**:
   ```bash
   ps aux | grep queue:work
   ```

2. **Start Queue Worker**:
   ```bash
   php artisan queue:work --tries=3
   ```

3. **Check Supervisor** (if using):
   ```bash
   sudo supervisorctl status
   sudo supervisorctl restart laravel-worker:*
   ```

4. **Check Queue Connection**:
   ```env
   QUEUE_CONNECTION=database
   # or
   QUEUE_CONNECTION=redis
   ```

5. **Check Failed Jobs**:
   ```bash
   php artisan queue:failed
   ```

### Jobs Failing Repeatedly

**Error**: Jobs in `failed_jobs` table

**Solutions**:

1. **View Failed Job Details**:
   ```bash
   php artisan queue:failed
   ```

2. **Retry Failed Job**:
   ```bash
   php artisan queue:retry {id}
   php artisan queue:retry all
   ```

3. **Check Job Logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Fix Underlying Issue**:
   - Check job code for errors
   - Verify external API connections
   - Check database constraints

### Queue Table Growing Unbounded

**Symptoms**: `jobs` table has thousands of rows

**Solutions**:

1. **Process Queue Faster**:
   - Increase number of workers
   - Use Redis queue driver
   - Optimize job execution time

2. **Clean Old Jobs**:
   ```sql
   DELETE FROM jobs WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
   ```

3. **Monitor Queue Size**:
   ```sql
   SELECT COUNT(*) FROM jobs;
   ```

---

## Payment Gateway Issues

### Payment Not Processing

**Symptoms**: Payment stuck in pending status

**Solutions**:

1. **Check Gateway Status**:
   ```php
   Gateway::where('status', 1)->get();
   ```

2. **Verify Gateway Credentials**:
   - Check `.env` for API keys
   - Verify gateway configuration in admin panel
   - Test gateway in sandbox mode

3. **Check Callback URL**:
   - Verify webhook URL is accessible
   - Check gateway callback logs
   - Ensure SSL certificate is valid

4. **Manual Approval** (for manual gateways):
   ```php
   $payment = Payment::where('trx', $trx)->first();
   $payment->update(['status' => 1]);
   ```

### Gateway Callback Not Received

**Symptoms**: Payment completed but status not updated

**Solutions**:

1. **Check Webhook Endpoint**:
   ```bash
   curl -X POST https://yourdomain.com/user/payment/success/paypal
   ```

2. **Verify Signature Validation**:
   - Check gateway service `success()` method
   - Verify signature verification logic
   - Check logs for validation errors

3. **Manual Callback Trigger**:
   - Some gateways allow manual callback testing
   - Use gateway dashboard to resend webhook

### Subscription Not Created After Payment

**Symptoms**: Payment approved but no subscription

**Solutions**:

1. **Check Payment Status**:
   ```php
   $payment = Payment::where('trx', $trx)->first();
   // Should be status = 1 (approved)
   ```

2. **Check Subscription Creation Logic**:
   - Verify `PaymentService` handles approval
   - Check `PlanService::createSubscription()` is called
   - Review payment callback handler

3. **Manually Create Subscription**:
   ```php
   PlanSubscription::create([
       'user_id' => $payment->user_id,
       'plan_id' => $payment->plan_id,
       'start_date' => now(),
       'end_date' => now()->addDays($plan->duration),
       'is_current' => 1,
       'status' => 'active',
   ]);
   ```

---

## Signal Distribution Issues

### Signals Not Sent to Users

**Symptoms**: Signal published but users not receiving

**Solutions**:

1. **Check Signal Status**:
   ```php
   $signal = Signal::find($id);
   // Should have is_published = 1
   ```

2. **Check Plan Assignment**:
   ```php
   $signal->plans; // Should have assigned plans
   ```

3. **Check User Subscriptions**:
   ```php
   $users = User::whereHas('subscriptions', function($q) use ($signal) {
       $q->where('plan_id', $signal->plans->first()->id)
         ->where('is_current', 1)
         ->where('status', 'active');
   })->get();
   ```

4. **Check Telegram Bot**:
   - Verify bot token in `.env`
   - Check users have `telegram_id` set
   - Test bot manually: `php artisan tinker` → `Telegram::sendMessage(...)`

5. **Check Queue Jobs**:
   - Verify `ProcessChannelMessage` jobs are processing
   - Check for failed jobs

### Telegram Notifications Not Sending

**Symptoms**: Telegram messages not delivered

**Solutions**:

1. **Verify Bot Token**:
   ```env
   TELEGRAM_BOT_TOKEN=your_bot_token
   ```

2. **Test Bot Connection**:
   ```bash
   curl https://api.telegram.org/bot{token}/getMe
   ```

3. **Check User Telegram IDs**:
   ```php
   User::whereNotNull('telegram_id')->get();
   ```

4. **Check Rate Limits**:
   - Telegram has rate limits (30 messages/second)
   - Queue messages if sending to many users

5. **Check Error Logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep telegram
   ```

---

## Multi-Channel Addon Issues

### Channel Messages Not Processing

**Symptoms**: Messages received but signals not created

**Solutions**:

1. **Check Channel Source Status**:
   ```php
   ChannelSource::where('status', 'active')->get();
   ```

2. **Check Message Status**:
   ```php
   ChannelMessage::where('status', 'pending')->get();
   ```

3. **Check Parser Configuration**:
   - Verify parser is enabled
   - Check parser rules/patterns
   - Test parser manually

4. **Check Queue Jobs**:
   ```bash
   php artisan queue:work
   ```

5. **Check Logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep channel
   ```

### Telegram Webhook Not Receiving Updates

**Symptoms**: Telegram channel not receiving messages

**Solutions**:

1. **Verify Webhook URL**:
   ```bash
   curl https://api.telegram.org/bot{token}/getWebhookInfo
   ```

2. **Set Webhook**:
   ```bash
   curl -X POST https://api.telegram.org/bot{token}/setWebhook \
     -d url=https://yourdomain.com/api/webhook/telegram/{channelSourceId}
   ```

3. **Check Channel Source ID**:
   - Verify `channelSourceId` in URL matches database
   - Check channel source exists and is active

4. **Check SSL Certificate**:
   - Telegram requires HTTPS for webhooks
   - Verify SSL certificate is valid

### AI Parsing Failing

**Symptoms**: AI parser returns errors or no results

**Solutions**:

1. **Check API Keys**:
   ```env
   OPENAI_API_KEY=your_key
   GEMINI_API_KEY=your_key
   OPENROUTER_API_KEY=your_key
   ```

2. **Check API Quotas**:
   - Verify API account has credits
   - Check rate limits

3. **Test API Connection**:
   ```php
   // In tinker
   $parser = app(\App\Parsers\AiMessageParser::class);
   $result = $parser->parse('test message');
   ```

4. **Check Parser Configuration**:
   - Verify AI provider is selected
   - Check model selection
   - Review prompt templates

---

## Execution Engine Issues

### Trades Not Executing

**Symptoms**: Signals published but trades not placed

**Solutions**:

1. **Check Execution Connection**:
   ```php
   ExecutionConnection::where('is_active', 1)->get();
   ```

2. **Verify Exchange Credentials**:
   - Check API keys are valid
   - Verify API permissions (trading enabled)
   - Test connection manually

3. **Check Preset Assignment**:
   ```php
   $connection->preset; // Should have trading preset
   ```

4. **Check Job Execution**:
   ```bash
   php artisan queue:work
   ```

5. **Check Error Logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep execution
   ```

### Position Monitoring Not Working

**Symptoms**: Positions not updating, SL/TP not triggering

**Solutions**:

1. **Check Scheduled Job**:
   ```bash
   crontab -l
   # Should have: * * * * * php artisan schedule:run
   ```

2. **Check MonitorPositionsJob**:
   ```bash
   php artisan schedule:list
   ```

3. **Verify Exchange API**:
   - Check API connection
   - Verify position data accessible
   - Test API calls manually

4. **Check Position Records**:
   ```php
   ExecutionPosition::where('status', 'open')->get();
   ```

---

## Performance Issues

### Slow Page Loads

**Symptoms**: Pages taking >3 seconds to load

**Solutions**:

1. **Enable OpCache**:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=256
   ```

2. **Use Redis Cache**:
   ```env
   CACHE_DRIVER=redis
   ```

3. **Optimize Database Queries**:
   - Add indexes
   - Use eager loading
   - Avoid N+1 queries

4. **Enable Query Caching**:
   ```php
   Cache::remember('key', 3600, function() {
       return DB::table('table')->get();
   });
   ```

5. **Use CDN for Assets**:
   ```env
   ASSET_URL=https://cdn.yourdomain.com
   ```

### High Memory Usage

**Symptoms**: PHP memory limit exceeded

**Solutions**:

1. **Increase Memory Limit**:
   ```ini
   memory_limit=512M
   ```

2. **Optimize Code**:
   - Use chunking for large datasets
   - Unset variables after use
   - Avoid loading all records at once

3. **Use Queue for Heavy Operations**:
   ```php
   dispatch(new HeavyJob($data));
   ```

---

## Security Issues

### Unauthorized Access

**Symptoms**: Users accessing admin routes

**Solutions**:

1. **Check Middleware**:
   ```php
   Route::middleware(['admin'])->group(...);
   ```

2. **Verify Permissions**:
   ```php
   $admin->hasPermissionTo('manage-plan');
   ```

3. **Check Guard**:
   ```php
   auth()->guard('admin')->check();
   ```

### CSRF Token Mismatch

**Error**: `419 Page Expired`

**Solutions**:

1. **Add CSRF Token to Forms**:
   ```blade
   @csrf
   ```

2. **Check Session Configuration**:
   ```env
   SESSION_DRIVER=database
   SESSION_LIFETIME=120
   ```

3. **Clear Browser Cache**:
   - Clear cookies
   - Try incognito mode

### SQL Injection Attempts

**Symptoms**: Suspicious queries in logs

**Solutions**:

1. **Always Use Eloquent**:
   ```php
   User::where('id', $id)->first();
   ```

2. **Use Parameterized Queries**:
   ```php
   DB::select('SELECT * FROM users WHERE id = ?', [$id]);
   ```

3. **Validate Input**:
   ```php
   $request->validate(['id' => 'required|integer']);
   ```

---

## Getting Help

If issues persist:

1. **Check Logs**: `storage/logs/laravel.log`
2. **Enable Debug Mode** (temporarily): `APP_DEBUG=true`
3. **Review Documentation**: Check relevant docs in `docs/` folder
4. **Check GitHub Issues**: Search for similar issues
5. **Contact Support**: Provide error logs and steps to reproduce

---

**Last Updated**: 2025-12-02
