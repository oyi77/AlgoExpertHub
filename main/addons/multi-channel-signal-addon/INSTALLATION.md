# Installation Guide: Multi-Channel Signal Addon

## Step 1: Install MadelineProto (for MTProto support)

```bash
cd /home/u875299794/domains/algoexperthub.com/public_html/main
composer require danog/madelineproto
```

## Step 2: Register Addon Service Provider

Add to `config/app.php` in the `providers` array:

```php
'providers' => [
    // ... existing providers ...
    
    Addons\MultiChannelSignalAddon\AddonServiceProvider::class,
],
```

## Step 3: Run Migrations

```bash
php artisan migrate
```

## Step 4: Update Composer Autoload

Add to `composer.json` in the `autoload.psr-4` section:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Addons\\MultiChannelSignalAddon\\": "addons/multi-channel-signal-addon/app/"
    }
}
```

Then run:
```bash
composer dump-autoload
```

## Step 5: Clear Cache

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## That's it! The addon is now active.


