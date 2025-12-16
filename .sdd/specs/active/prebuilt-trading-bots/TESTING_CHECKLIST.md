# Prebuilt Trading Bots - Testing Checklist

## Migration Tests

- [ ] Run migrations: `php artisan migrate`
  - [ ] `2025_01_30_100000_add_template_fields_to_trading_bots_table.php` executes successfully
  - [ ] `2025_01_30_100001_allow_null_exchange_connection_for_templates.php` executes successfully
  - [ ] Verify columns added: visibility, clonable, is_default_template, created_by_user_id, suggested_connection_type, tags
  - [ ] Verify exchange_connection_id can be NULL

## Seeder Tests

- [ ] Run seeder: `php artisan db:seed --class="Addons\TradingManagement\Database\Seeders\PrebuiltTradingBotSeeder"`
  - [ ] Creates 5 filter strategies with MA100/MA10/PSAR
  - [ ] Creates 6 bot templates
  - [ ] All templates have is_default_template = true
  - [ ] All templates have visibility = PUBLIC_MARKETPLACE
  - [ ] All templates have clonable = true
  - [ ] All templates have exchange_connection_id = NULL

## Model Tests

- [ ] TradingBot model scopes work:
  - [ ] `TradingBot::defaultTemplates()->count()` > 0
  - [ ] `TradingBot::public()->count()` > 0
  - [ ] `TradingBot::clonable()->count()` > 0
  - [ ] `TradingBot::templates()->count()` > 0

- [ ] TradingBot helper methods work:
  - [ ] `$template->isPublic()` returns true for public templates
  - [ ] `$template->isClonable()` returns true
  - [ ] `$template->isDefaultTemplate()` returns true
  - [ ] `$template->isTemplate()` returns true

## Service Tests

- [ ] `TradingBotService::getPrebuiltTemplates()` returns templates
- [ ] `TradingBotService::getBots()` excludes templates
- [ ] Filter by connection_type works
- [ ] Filter by tags works
- [ ] Search works

## Controller Tests

- [ ] `/user/trading-bots/marketplace` loads successfully
- [ ] Marketplace shows all templates
- [ ] Filter by market type works
- [ ] Search works

- [ ] `/user/trading-bots/clone/{id}` loads successfully
- [ ] Shows template preview
- [ ] Shows user's connections (filtered by type)
- [ ] Validates connection type matches template suggestion

- [ ] POST `/user/trading-bots/clone/{id}` works:
  - [ ] Creates cloned bot
  - [ ] Uses user's connection
  - [ ] Clones preset if public/clonable
  - [ ] Clones filter if public/clonable
  - [ ] Returns success message

## Clone Logic Tests

- [ ] Clone with matching connection type works
- [ ] Clone with mismatched connection type fails with error
- [ ] Clone with 'both' type accepts any connection
- [ ] Cloned bot has is_default_template = false
- [ ] Cloned bot has visibility = PRIVATE
- [ ] Cloned bot has user_id set
- [ ] Cloned bot has exchange_connection_id set to user's connection
- [ ] Cloned bot starts as inactive

## Filter Strategy Tests

- [ ] Filter strategies created with correct indicators:
  - [ ] MA10/MA100/PSAR Uptrend Filter has ema_fast, ema_slow, psar
  - [ ] Rules structure uses 'logic' and 'conditions' (not 'operator')
  - [ ] All filters use MA100, MA10, or PSAR

- [ ] Filter evaluator handles "price" comparisons:
  - [ ] `price > ema_slow` works correctly
  - [ ] `psar below_price` works correctly

## UI Tests

- [ ] Marketplace page displays correctly:
  - [ ] Grid view of templates
  - [ ] Shows template name, description
  - [ ] Shows indicators (MA100, MA10, PSAR badges)
  - [ ] Shows suggested connection type
  - [ ] "Clone Template" button works

- [ ] Clone page displays correctly:
  - [ ] Template preview card
  - [ ] Connection dropdown (filtered)
  - [ ] Bot name input (pre-filled)
  - [ ] Paper trading toggle (checked by default)

- [ ] Index page updated:
  - [ ] "Browse Templates" button visible
  - [ ] Templates don't appear in user bot list

- [ ] Create page updated:
  - [ ] "Browse Templates" link visible

## Integration Tests

- [ ] User can browse templates
- [ ] User can clone template
- [ ] User can customize cloned bot
- [ ] Cloned bot appears in user's bot list
- [ ] Template does NOT appear in user's bot list

## Edge Cases

- [ ] User with no connections sees error message
- [ ] User with wrong connection type sees filtered list or error
- [ ] Cloning non-clonable template fails
- [ ] Cloning private template (not owned) fails
- [ ] Template with NULL connection_id works

## Demo Readiness

- [ ] All 6 templates are visible in marketplace
- [ ] All templates clearly show MA100/MA10/PSAR usage
- [ ] Descriptions are professional and demo-ready
- [ ] Clone process is smooth and intuitive

## Performance

- [ ] Marketplace loads quickly (< 2 seconds)
- [ ] Clone operation completes quickly (< 1 second)
- [ ] No N+1 query issues
