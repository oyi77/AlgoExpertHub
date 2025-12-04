# Prebuilt Trading Bots

## Concept

Trading bots should have **prebuilt templates** (similar to Trading Presets) that users can browse, clone, and customize.

**Similar to Coinrule**: Users select a prebuilt bot template, then customize it with their exchange connection.

## Current State

✅ Trading Bots exist (`TradingBot` model)  
✅ Trading Presets have prebuilt templates (6 default presets)  
✅ Filter Strategies have visibility/clonable system  
✅ Clone pattern exists (`cloneForUser()` method)  
❌ Trading Bots have NO prebuilt templates  
❌ TradingBot model missing visibility/clonable fields  
❌ No bot template seeder  
❌ No bot marketplace UI  

## Documentation

- **feature-brief.md** - High-level concept and requirements
- **plan.md** - Detailed implementation plan and architecture
- **tasks.md** - Task breakdown with implementation details
- **bd-issues.md** - BD commands to create tracking issues

## Quick Start

1. Read **feature-brief.md** to understand the concept
2. Read **plan.md** for architecture and technical details
3. Follow **tasks.md** for step-by-step implementation
4. Use **bd-issues.md** to create tracking issues

## Key Features

- Prebuilt bot templates (6+ templates)
- Bot marketplace (browse templates)
- Clone functionality (user clones template with their connection)
- Template customization (preset/filter/AI cloning if public)
- Admin template creation (admins can create template bots)

## Implementation Phases

1. **Database & Model** - Add fields, scopes, clone method
2. **Service Layer** - Add template methods
3. **Seeder** - Create prebuilt templates
4. **Controllers** - Add marketplace and clone endpoints
5. **Views** - Create marketplace and clone UI
6. **Routes & Navigation** - Wire everything together
