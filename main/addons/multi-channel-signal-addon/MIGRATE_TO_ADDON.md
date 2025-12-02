# Migration Guide: Moving to Addon Structure

This guide helps migrate the existing implementation to the modular addon structure.

## Step 1: Update Namespaces

All files in the addon use the namespace: `Addons\MultiChannelSignalAddon\App\...`

## Step 2: Update Database Migration

The migration needs to support `telegram_mtproto` type:

```sql
ALTER TABLE channel_sources MODIFY COLUMN type ENUM('telegram', 'telegram_mtproto', 'api', 'web_scrape', 'rss') DEFAULT 'telegram';
```

## Step 3: Copy Files

Run the migration script or manually copy files to addon directory.

## Step 4: Update Signal Model

The Signal model needs to be extended to support the addon namespace. Create a service provider extension.


