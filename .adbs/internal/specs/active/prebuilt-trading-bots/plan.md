# Prebuilt Trading Bots - Implementation Plan

## Overview

Implement prebuilt trading bot templates system, similar to Trading Presets. Users can browse, clone, and customize prebuilt bots.

## Architecture

### Database Schema Updates

Add to `trading_bots` table:
- `visibility` ENUM('PRIVATE', 'PUBLIC_MARKETPLACE') DEFAULT 'PRIVATE'
- `clonable` BOOLEAN DEFAULT true
- `is_default_template` BOOLEAN DEFAULT false
- `created_by_user_id` BIGINT NULLABLE (NULL for system templates)
- `suggested_connection_type` ENUM('crypto', 'fx', 'both') NULLABLE (hint for template)
- `tags` JSON NULLABLE (for categorization)

**Migration**: `2025_01_30_100000_add_template_fields_to_trading_bots_table.php`

### Model Updates

**TradingBot Model** (`main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php`):

Add:
- Scopes: `defaultTemplates()`, `public()`, `clonable()`, `byUser($userId)`
- Methods: `isPublic()`, `isClonable()`, `isDefaultTemplate()`, `canBeClonedBy($user)`, `cloneForUser($user, $connectionId)`
- Casts: `clonable` (boolean), `is_default_template` (boolean), `tags` (array)

**Clone Logic**:
1. Clone bot (set user_id, visibility=PRIVATE, is_default_template=false)
2. User must provide exchange_connection_id (template doesn't have one)
3. Clone preset if public/clonable, otherwise use user's default or template's preset
4. Clone filter if exists and public/clonable
5. Clone AI profile if exists and public/clonable

### Service Updates

**TradingBotService** (`main/addons/trading-management-addon/Modules/TradingBot/Services/TradingBotService.php`):

Add methods:
- `getPrebuiltTemplates($filters = [])` - Get all public/default templates
- `cloneTemplate($templateId, $userId, $connectionId, $options = [])` - Clone template for user
- `getTemplatesForMarketplace()` - Get templates for marketplace UI

### Seeder

**PrebuiltTradingBotSeeder** (`main/addons/trading-management-addon/database/seeders/PrebuiltTradingBotSeeder.php`):

Create 6+ prebuilt bot templates:
1. Conservative Forex Bot
2. Aggressive Crypto Bot
3. AI-Enhanced Swing Bot
4. Pure AI Bot (No Filter)
5. Conservative No-Filter Bot
6. Multi-Pair Moderate Bot

**Template Structure**:
- `name`, `description`
- `trading_preset_id` (lookup by name: "Conservative Scalper")
- `filter_strategy_id` (lookup by name, nullable)
- `ai_model_profile_id` (lookup by name, nullable)
- `suggested_connection_type` ('fx', 'crypto', 'both')
- `is_default_template` = true
- `visibility` = 'PUBLIC_MARKETPLACE'
- `clonable` = true
- `user_id` = null
- `admin_id` = null
- `exchange_connection_id` = **NOT SET** (user provides during clone)
- `tags` = ['forex', 'conservative', 'scalping'] etc.

### Controller Updates

**TradingBotController** (User):
- `marketplace()` - Browse prebuilt templates
- `clone($templateId)` - Clone template (GET: show form, POST: process clone)

**TradingBotController** (Backend):
- `templates()` - Manage templates (admin)
- `createTemplate()` - Create new template (admin)

### Views

**User Views**:
- `marketplace.blade.php` - Browse templates (grid/list view)
- `clone.blade.php` - Clone form (select connection, customize name)
- Update `create.blade.php` - Add "Start from Template" button

**Backend Views**:
- `templates/index.blade.php` - Manage templates
- Update bot forms to support template creation

### Routes

**User Routes**:
```php
Route::get('/trading-bots/marketplace', [TradingBotController::class, 'marketplace'])->name('trading-bots.marketplace');
Route::get('/trading-bots/clone/{template}', [TradingBotController::class, 'clone'])->name('trading-bots.clone');
Route::post('/trading-bots/clone/{template}', [TradingBotController::class, 'clone'])->name('trading-bots.clone.store');
```

**Backend Routes**:
```php
Route::get('/trading-bots/templates', [TradingBotController::class, 'templates'])->name('trading-bots.templates');
```

## Implementation Tasks

### Phase 1: Database & Model (Foundation)

1. **Migration**: Add template fields to trading_bots table
   - Fields: visibility, clonable, is_default_template, created_by_user_id, suggested_connection_type, tags
   - Indexes: visibility, is_default_template, created_by_user_id

2. **Model**: Update TradingBot model
   - Add casts for new fields
   - Add scopes (defaultTemplates, public, clonable, byUser)
   - Add helper methods (isPublic, isClonable, isDefaultTemplate)
   - Add permission methods (canBeClonedBy)

3. **Model**: Add cloneForUser method
   - Clone bot with user's connection
   - Clone referenced preset/filter/AI if needed
   - Return cloned bot instance

### Phase 2: Service Layer

4. **Service**: Update TradingBotService
   - getPrebuiltTemplates() method
   - cloneTemplate() method (handles full clone logic)
   - Update getBots() to exclude templates by default

5. **Service**: Clone logic implementation
   - Validate template can be cloned
   - Validate user has connection
   - Clone preset if needed (or use existing)
   - Clone filter if needed (or use existing)
   - Clone AI profile if needed (or use existing)
   - Create bot with cloned references

### Phase 3: Seeder

6. **Seeder**: Create PrebuiltTradingBotSeeder
   - Lookup presets by name (get IDs)
   - Lookup filters by name (nullable)
   - Lookup AI profiles by name (nullable)
   - Create 6+ template bots
   - Register seeder in DatabaseSeeder

### Phase 4: Controllers

7. **Controller**: User TradingBotController
   - marketplace() - List all prebuilt templates
   - clone($templateId) - GET: show clone form, POST: process clone
   - Update index() - Filter out templates (show only user bots)

8. **Controller**: Backend TradingBotController
   - templates() - Manage templates (admin)
   - Update create/edit - Support template creation

### Phase 5: Views

9. **View**: User marketplace.blade.php
   - Grid/list view of templates
   - Filter by market type, tags
   - "Clone" button per template
   - Template details (preset, filter, AI used)

10. **View**: User clone.blade.php
    - Select exchange connection (required)
    - Bot name (default: template name + " (Copy)")
    - Customize preset/filter/AI (optional, show current template's)
    - Submit to clone

11. **View**: Update create.blade.php
    - Add "Browse Templates" button/link
    - Option to start from template

12. **View**: Backend templates/index.blade.php
    - List all templates
    - Create/edit/delete templates
    - Toggle visibility/clonable

### Phase 6: Routes & Navigation

13. **Routes**: Add marketplace and clone routes
    - User routes
    - Backend routes (if needed)

14. **Navigation**: Add marketplace link
    - User menu: "Bot Marketplace" or "Templates"
    - In create bot page: "Start from Template"

## Technical Considerations

### Connection Dependency

**Problem**: Templates can't have fixed connections (users must use their own)

**Solution**:
- Templates store `suggested_connection_type` ('fx', 'crypto', 'both')
- During clone, user selects their connection (filtered by type)
- Validation: Ensure connection type matches suggestion

### Preset/Filter/AI Lookup

**Problem**: Templates reference presets/filters/AI by ID, but we want to use names in seeder

**Solution**:
- Seeder looks up by name:
  ```php
  $preset = TradingPreset::where('name', 'Conservative Scalper')
      ->where('is_default_template', true)
      ->first();
  ```
- Store IDs in template
- During clone, if preset/filter/AI is public/clonable, clone it; otherwise use existing

### Template vs User Bot

**Problem**: Distinguish templates from user bots in queries

**Solution**:
- `is_default_template = true` OR `created_by_user_id = null` = template
- Scope: `defaultTemplates()` filters templates
- User queries: `whereNotNull('user_id')` or `where('is_default_template', false)`

### Admin Templates

**Problem**: Admins should be able to create template bots

**Solution**:
- `admin_id` set + `visibility = PUBLIC_MARKETPLACE` = admin template
- `is_default_template = false` (for admin-created) OR `true` (for system templates)
- Both appear in marketplace

## Success Criteria

✅ Migration runs successfully  
✅ TradingBot model has all template methods  
✅ Seeder creates 6+ prebuilt templates  
✅ Users can browse marketplace  
✅ Users can clone templates  
✅ Cloned bots use user's connections  
✅ Cloned presets/filters/AI are created if needed  
✅ Marketplace UI is functional  
✅ Admin can create template bots  

## Dependencies

- Trading Presets must have prebuilt templates (✅ exists)
- Filter Strategies should have some public templates (✅ exists)
- AI Profiles should have some public templates (need to verify)
- Exchange Connections must exist for users (user creates)

## Testing Checklist

- [ ] Migration creates fields correctly
- [ ] Seeder creates templates
- [ ] Model scopes work correctly
- [ ] cloneForUser() clones correctly
- [ ] Marketplace shows templates
- [ ] Clone form validates connection
- [ ] Clone creates bot with user's connection
- [ ] Cloned preset/filter/AI are created if needed
- [ ] Template bots don't appear in user's bot list
- [ ] Admin can create template bots
