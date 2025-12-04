# Prebuilt Trading Bots - BD Issues

Use these commands to create bd issues:

```bash
# Phase 1: Database & Model Foundation
bd create "Add template fields to trading_bots table migration" -t task -p 1 --json
bd create "Update TradingBot model - add fields, casts, scopes" -t task -p 1 --json
bd create "Add cloneForUser method to TradingBot model" -t task -p 1 --json

# Phase 2: Service Layer  
bd create "Update TradingBotService - add getPrebuiltTemplates method" -t task -p 1 --json
bd create "Update TradingBotService - add cloneTemplate method" -t task -p 1 --json
bd create "Update TradingBotService - exclude templates from getBots" -t task -p 2 --json

# Phase 3: Seeder
bd create "Create PrebuiltTradingBotSeeder with 6+ templates" -t task -p 1 --json
bd create "Register PrebuiltTradingBotSeeder in DatabaseSeeder" -t task -p 2 --json

# Phase 4: Controllers
bd create "User TradingBotController - add marketplace() method" -t task -p 1 --json
bd create "User TradingBotController - add clone() and storeClone() methods" -t task -p 1 --json
bd create "Update User TradingBotController index() to exclude templates" -t task -p 2 --json

# Phase 5: Views
bd create "Create marketplace.blade.php view for browsing templates" -t task -p 1 --json
bd create "Create clone.blade.php view for cloning templates" -t task -p 1 --json
bd create "Update create.blade.php - add 'Browse Templates' option" -t task -p 2 --json
bd create "Update index.blade.php - ensure templates excluded" -t task -p 2 --json

# Phase 6: Routes & Navigation
bd create "Add marketplace and clone routes to user routes" -t task -p 1 --json
bd create "Add 'Bot Marketplace' link to user navigation menu" -t task -p 2 --json

# Testing
bd create "Test migration, seeder, model methods, and clone functionality" -t task -p 1 --json
```

## Summary

**Total Tasks**: 16  
**Priority 1 (Critical)**: 10 tasks  
**Priority 2 (Important)**: 6 tasks

**Estimated Effort**:
- Phase 1: 4-6 hours
- Phase 2: 2-3 hours  
- Phase 3: 2-3 hours
- Phase 4: 3-4 hours
- Phase 5: 4-6 hours
- Phase 6: 1-2 hours
- Testing: 2-3 hours

**Total**: 18-27 hours
