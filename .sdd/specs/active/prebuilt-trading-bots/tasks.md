# Prebuilt Trading Bots - Task Breakdown

## Phase 1: Database & Model Foundation

### Task 1.1: Create Migration
**File**: `main/addons/trading-management-addon/database/migrations/2025_01_30_100000_add_template_fields_to_trading_bots_table.php`

**Fields to add**:
- `visibility` ENUM('PRIVATE', 'PUBLIC_MARKETPLACE') DEFAULT 'PRIVATE'
- `clonable` BOOLEAN DEFAULT true
- `is_default_template` BOOLEAN DEFAULT false
- `created_by_user_id` BIGINT UNSIGNED NULLABLE
- `suggested_connection_type` ENUM('crypto', 'fx', 'both') NULLABLE
- `tags` JSON NULLABLE

**Indexes**:
- `visibility`
- `is_default_template`
- `created_by_user_id`

**Foreign Keys**:
- `created_by_user_id` → `users.id` ON DELETE SET NULL

---

### Task 1.2: Update TradingBot Model - Fields & Casts
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php`

**Add to $fillable**:
- `visibility`, `clonable`, `is_default_template`, `created_by_user_id`, `suggested_connection_type`, `tags`

**Add to $casts**:
- `clonable` => 'boolean'
- `is_default_template` => 'boolean'
- `tags` => 'array'

---

### Task 1.3: Update TradingBot Model - Scopes
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php`

**Add scopes**:
```php
public function scopeDefaultTemplates($query)
{
    return $query->where('is_default_template', true);
}

public function scopePublic($query)
{
    return $query->where('visibility', 'PUBLIC_MARKETPLACE');
}

public function scopePrivate($query)
{
    return $query->where('visibility', 'PRIVATE');
}

public function scopeClonable($query)
{
    return $query->where('clonable', true);
}

public function scopeByUser($query, int $userId)
{
    return $query->where('created_by_user_id', $userId);
}

public function scopeTemplates($query)
{
    return $query->where(function ($q) {
        $q->where('is_default_template', true)
          ->orWhereNull('created_by_user_id');
    });
}
```

---

### Task 1.4: Update TradingBot Model - Helper Methods
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php`

**Add methods**:
```php
public function isPublic(): bool
{
    return $this->visibility === 'PUBLIC_MARKETPLACE';
}

public function isClonable(): bool
{
    return $this->clonable === true;
}

public function isDefaultTemplate(): bool
{
    return $this->is_default_template === true;
}

public function isTemplate(): bool
{
    return $this->is_default_template || is_null($this->created_by_user_id);
}

public function canBeClonedBy($user): bool
{
    if (!$user) return false;
    
    // If not clonable, only creator or admin can clone
    if (!$this->isClonable()) {
        return $this->canBeEditedBy($user);
    }
    
    // Public templates can be cloned by anyone
    if ($this->isPublic()) {
        return true;
    }
    
    // Private templates only by creator/admin
    return $this->canBeEditedBy($user);
}

public function canBeEditedBy($user): bool
{
    if (!$user) return false;
    
    // Admins can edit any
    if (isset($user->type) && $user->type === 'super') {
        return true;
    }
    
    // Users can edit their own
    if ($this->created_by_user_id === $user->id) {
        return true;
    }
    
    // Default templates cannot be edited by non-admins
    if ($this->is_default_template) {
        return false;
    }
    
    return false;
}
```

---

### Task 1.5: Add cloneForUser Method
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Models/TradingBot.php`

**Implementation**:
```php
public function cloneForUser($user, int $connectionId, array $options = [])
{
    // Validate clone permission
    if (!$this->canBeClonedBy($user)) {
        throw new \Exception('Bot template cannot be cloned by this user');
    }
    
    // Validate connection belongs to user
    $connection = ExchangeConnection::findOrFail($connectionId);
    if ($connection->user_id !== $user->id) {
        throw new \Exception('Connection does not belong to user');
    }
    
    // Validate connection type matches suggestion (if set)
    if ($this->suggested_connection_type) {
        $connectionType = $connection->type; // 'crypto' or 'fx'
        if ($this->suggested_connection_type !== 'both' && 
            $this->suggested_connection_type !== $connectionType) {
            throw new \Exception("Connection type must be {$this->suggested_connection_type}");
        }
    }
    
    // Clone preset if needed
    $presetId = $this->trading_preset_id;
    if ($this->tradingPreset) {
        $preset = $this->tradingPreset;
        if ($preset->isPublic() && $preset->isClonable()) {
            $clonedPreset = $preset->cloneFor($user);
            $presetId = $clonedPreset->id;
        } else {
            // Use existing preset or user's default
            $presetId = $user->default_preset_id ?? $presetId;
        }
    }
    
    // Clone filter if needed
    $filterId = $this->filter_strategy_id;
    if ($filterId && $this->filterStrategy) {
        $filter = $this->filterStrategy;
        if ($filter->isPublic() && $filter->isClonable()) {
            $clonedFilter = $filter->cloneForUser($user->id);
            $filterId = $clonedFilter->id;
        } else {
            $filterId = null; // User can set their own
        }
    }
    
    // Clone AI profile if needed
    $aiProfileId = $this->ai_model_profile_id;
    if ($aiProfileId && $this->aiModelProfile) {
        $aiProfile = $this->aiModelProfile;
        if ($aiProfile->isPublic() && $aiProfile->isClonable()) {
            // Clone AI profile (if clone method exists)
            // $clonedAi = $aiProfile->cloneFor($user);
            // $aiProfileId = $clonedAi->id;
        } else {
            $aiProfileId = null; // User can set their own
        }
    }
    
    // Create cloned bot
    $clonedBot = self::create([
        'user_id' => $user->id,
        'admin_id' => null,
        'name' => $options['name'] ?? ($this->name . ' (Copy)'),
        'description' => $this->description,
        'exchange_connection_id' => $connectionId,
        'trading_preset_id' => $presetId,
        'filter_strategy_id' => $filterId,
        'ai_model_profile_id' => $aiProfileId,
        'is_active' => false, // Start inactive, user activates
        'is_paper_trading' => $options['is_paper_trading'] ?? true,
        'visibility' => 'PRIVATE',
        'clonable' => false,
        'is_default_template' => false,
        'created_by_user_id' => $user->id,
        'suggested_connection_type' => null, // Not needed for user bots
        'tags' => null, // User can set their own tags
    ]);
    
    return $clonedBot;
}
```

---

## Phase 2: Service Layer

### Task 2.1: Update TradingBotService - getPrebuiltTemplates
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Services/TradingBotService.php`

**Add method**:
```php
public function getPrebuiltTemplates(array $filters = [])
{
    $query = TradingBot::with(['tradingPreset', 'filterStrategy', 'aiModelProfile'])
        ->where(function ($q) {
            $q->where('is_default_template', true)
              ->orWhereNull('created_by_user_id');
        })
        ->where('visibility', 'PUBLIC_MARKETPLACE');
    
    // Filter by market type
    if (isset($filters['connection_type'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('suggested_connection_type', $filters['connection_type'])
              ->orWhere('suggested_connection_type', 'both');
        });
    }
    
    // Filter by tags
    if (isset($filters['tags']) && is_array($filters['tags'])) {
        foreach ($filters['tags'] as $tag) {
            $query->whereJsonContains('tags', $tag);
        }
    }
    
    // Search
    if (isset($filters['search'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['search'] . '%')
              ->orWhere('description', 'like', '%' . $filters['search'] . '%');
        });
    }
    
    return $query->orderBy('name')->paginate($filters['per_page'] ?? 12);
}
```

---

### Task 2.2: Update TradingBotService - cloneTemplate
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Services/TradingBotService.php`

**Add method**:
```php
public function cloneTemplate(int $templateId, int $userId, int $connectionId, array $options = [])
{
    $user = User::findOrFail($userId);
    $template = TradingBot::findOrFail($templateId);
    
    // Validate template is clonable
    if (!$template->isTemplate()) {
        throw new \Exception('This bot is not a template');
    }
    
    // Clone using model method
    return $template->cloneForUser($user, $connectionId, $options);
}
```

---

### Task 2.3: Update TradingBotService - getBots (exclude templates)
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Services/TradingBotService.php`

**Update getBots method**:
Add filter to exclude templates:
```php
// Exclude templates (only show user bots)
$query->where(function ($q) {
    $q->whereNotNull('user_id')
      ->where('is_default_template', false);
});
```

---

## Phase 3: Seeder

### Task 3.1: Create PrebuiltTradingBotSeeder
**File**: `main/addons/trading-management-addon/database/seeders/PrebuiltTradingBotSeeder.php`

**Implementation**:
- **FIRST**: Create demo filter strategies (MA100/MA10/PSAR based) if not exist
- Lookup presets by name (from TradingPresetSeeder)
- Lookup filters by name (nullable, create if not exist)
- Lookup AI profiles by name (nullable)
- Create 6+ template bots with MA100/MA10/PSAR filters
- Use DB::transaction for safety

**Filter Strategies to Create** (if not exist):
1. **MA10/MA100/PSAR Uptrend Filter**
   - Indicators: ema_fast (10), ema_slow (100), psar
   - Rules: MA10 > MA100 AND PSAR below_price (for BUY)

2. **MA Crossover Filter**
   - Indicators: ema_fast (10), ema_slow (100), psar
   - Rules: MA10 > MA100 AND PSAR below_price (crossover entry)

3. **Strong Trend Filter**
   - Indicators: ema_fast (10), ema_slow (100), psar
   - Rules: Price > MA100 AND MA10 > MA100 AND PSAR below_price

4. **MA100 Support Filter**
   - Indicators: ema_fast (10), ema_slow (100), psar
   - Rules: Price near MA100 (support bounce) AND PSAR confirms

5. **Basic MA Filter**
   - Indicators: ema_fast (10), ema_slow (100), psar
   - Rules: MA10 > MA100 AND PSAR below_price

6. **Comprehensive MA/PSAR Filter**
   - Indicators: ema_fast (10), ema_slow (100), psar
   - Rules: MA10 > MA100 AND PSAR below_price AND Price > MA100

**Templates to create** (All using MA100, MA10, PSAR):
1. **MA Trend Confirmation Bot** (Forex) - MA10 > MA100 + PSAR below price
2. **MA10/MA100 Crossover Bot** (Forex) - MA crossover with PSAR confirmation
3. **MA100 + PSAR Trend Follower** (Crypto) - Strong trend filter (Price > MA100, PSAR below, MA10 > MA100)
4. **MA100 Support/Resistance Bot** (Forex) - Bounces off MA100 with PSAR
5. **Conservative MA Trend Bot** (Multi) - Simple MA10 > MA100 + PSAR
6. **Advanced MA + PSAR Multi-Strategy** (Forex) - Comprehensive filter showcasing all indicators

**Filter Strategies to create** (if not exist):
- MA10/MA100/PSAR Uptrend Filter
- MA Crossover Filter
- Strong Trend Filter (MA100 + PSAR)
- MA100 Support Filter
- Basic MA Filter
- Comprehensive MA/PSAR Filter

---

### Task 3.2: Register Seeder
**File**: `main/database/seeders/DatabaseSeeder.php`

Add:
```php
$this->call([
    // ... existing seeders
    \Addons\TradingManagement\Database\Seeders\PrebuiltTradingBotSeeder::class,
]);
```

---

## Phase 4: Controllers

### Task 4.1: User TradingBotController - marketplace()
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Controllers/User/TradingBotController.php`

**Add method**:
```php
public function marketplace(Request $request)
{
    $filters = [
        'connection_type' => $request->get('type'),
        'tags' => $request->get('tags', []),
        'search' => $request->get('search'),
        'per_page' => 12,
    ];
    
    $templates = $this->tradingBotService->getPrebuiltTemplates($filters);
    
    return view('trading-management::user.trading-bots.marketplace', compact('templates'));
}
```

---

### Task 4.2: User TradingBotController - clone()
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Controllers/User/TradingBotController.php`

**Add methods**:
```php
public function clone(TradingBot $template)
{
    // Validate template
    if (!$template->isTemplate()) {
        return redirect()->route('user.trading-bots.marketplace')
            ->with('error', 'This bot is not a template');
    }
    
    // Get user's connections (filtered by template's suggested type)
    $connections = $this->tradingBotService->getAvailableConnections();
    if ($template->suggested_connection_type) {
        $connections = $connections->filter(function ($conn) use ($template) {
            return $template->suggested_connection_type === 'both' ||
                   $conn->type === $template->suggested_connection_type;
        });
    }
    
    return view('trading-management::user.trading-bots.clone', compact('template', 'connections'));
}

public function storeClone(Request $request, TradingBot $template)
{
    $request->validate([
        'exchange_connection_id' => 'required|exists:exchange_connections,id',
        'name' => 'nullable|string|max:255',
        'is_paper_trading' => 'boolean',
    ]);
    
    try {
        $bot = $this->tradingBotService->cloneTemplate(
            $template->id,
            Auth::id(),
            $request->exchange_connection_id,
            [
                'name' => $request->name,
                'is_paper_trading' => $request->boolean('is_paper_trading', true),
            ]
        );
        
        return redirect()->route('user.trading-bots.show', $bot)
            ->with('success', 'Bot cloned successfully!');
    } catch (\Exception $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', $e->getMessage());
    }
}
```

---

### Task 4.3: Update User TradingBotController - index()
**File**: `main/addons/trading-management-addon/Modules/TradingBot/Controllers/User/TradingBotController.php`

Ensure templates are excluded in index listing.

---

## Phase 5: Views

### Task 5.1: Create marketplace.blade.php
**File**: `main/addons/trading-management-addon/Modules/TradingBot/resources/views/user/trading-bots/marketplace.blade.php`

**Features**:
- Grid/list view of templates
- Filter by market type (Forex, Crypto, Both)
- Filter by tags
- Search box
- Template card showing: name, description, preset, filter, AI, tags
- "Clone" button per template

---

### Task 5.2: Create clone.blade.php
**File**: `main/addons/trading-management-addon/Modules/TradingBot/resources/views/user/trading-bots/clone.blade.php`

**Features**:
- Template preview (name, description, components)
- Select exchange connection dropdown (required)
- Bot name input (default: template name + " (Copy)")
- Paper trading toggle
- Submit button

---

### Task 5.3: Update create.blade.php
**File**: `main/addons/trading-management-addon/Modules/TradingBot/resources/views/user/trading-bots/create.blade.php`

Add:
- "Browse Templates" button/link at top
- Or tab: "Create New" | "Start from Template"

---

### Task 5.4: Update index.blade.php
**File**: `main/addons/trading-management-addon/Modules/TradingBot/resources/views/user/trading-bots/index.blade.php`

Ensure templates don't appear in user's bot list.

---

## Phase 6: Routes & Navigation

### Task 6.1: Add Routes
**File**: `main/addons/trading-management-addon/routes/user.php`

Add:
```php
Route::get('/trading-bots/marketplace', [TradingBotController::class, 'marketplace'])->name('trading-bots.marketplace');
Route::get('/trading-bots/clone/{template}', [TradingBotController::class, 'clone'])->name('trading-bots.clone');
Route::post('/trading-bots/clone/{template}', [TradingBotController::class, 'storeClone'])->name('trading-bots.clone.store');
```

---

### Task 6.2: Update Navigation
Add "Bot Marketplace" or "Templates" link to user menu.

---

## Testing

### Test Cases
1. Migration runs successfully
2. Seeder creates 6+ templates
3. Model scopes return correct results
4. cloneForUser() works correctly
5. Marketplace shows templates
6. Clone form validates inputs
7. Clone creates bot with user's connection
8. Cloned preset/filter/AI are created if public/clonable
9. Templates don't appear in user bot list
10. User can't clone non-clonable templates
11. Connection type validation works
