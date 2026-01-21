# Trading Bot Refactoring with Dynamic Configuration and Multi-Market Support

**Change ID:** trading-bot-refactor  
**Created:** 2026-01-19  
**Status:** Pending Review  
**Author:** Sisyphus AI Agent  
**Priority:** P0 - Core Feature Enhancement

---

## Executive Summary

Refactoring trading bot functionality untuk enable dynamic configuration, multi-market support (crypto + forex), dan comprehensive test coverage dengan TDD approach.

**Target Outcomes:**
1. Dynamic bot configuration tanpa restart
2. Unified crypto + forex trading interface
3. Demo/Testnet/Production mode isolation
4. 80% unit test coverage
5. Hot-reload capability untuk config changes

---

## Problem Statement

### Current Issues

| Issue | Impact | Severity |
|-------|--------|----------|
| Bot configuration static - requires restart | User experience degraded | 🔴 High |
| Config changes tidak apply otomatis | Confusion, potential losses | 🔴 High |
| Forex market support terbatas | Market coverage incomplete | 🟡 Medium |
| Demo mode tidak fully isolated | Testing tidak reliable | 🟡 Medium |
| Test coverage unknown | Refactoring risk tinggi | 🟡 Medium |

### User Requirements

1. **Dynamic Bot Configuration** - Add/edit bots dynamically tanpa restart
2. **Multi-Market Support** - Crypto (CCXT) + Forex (MetaApi)
3. **Demo/Testnet/Production** - Full isolation per mode
4. **Testable** - All functionality must be testable dengan TDD

---

## Scope

### In Scope

**Files to Modify:**
- `main/addons/trading-management-addon/Modules/TradingBot/` - Core bot logic
- `main/addons/trading-management-addon/Modules/Execution/` - Trade execution
- `main/addons/trading-management-addon/Modules/RiskManagement/` - Risk calculations
- `main/addons/trading-management-addon/Modules/ExchangeConnection/` - Exchange integration
- `main/app/Services/Trading/` - Trading services

**New Files to Create:**
- `TradingBotConfigManager.php` - Hot-reload config management
- `MarketRouter.php` - Unified market interface
- `PaperTradingService.php` - Demo mode simulation
- `SymbolNormalizer.php` - Cross-market symbol handling
- `tests/Unit/TradingBot/` - Unit tests (80% coverage)

### Out of Scope

- Frontend UI changes (delegated to frontend-ui-ux-engineer)
- New exchange integrations (beyond CCXT/MetaApi)
- Performance optimization (beyond refactoring requirements)

---

## Success Criteria

### Functional Criteria

- [ ] Bot config dapat diupdate tanpa restart
- [ ] Bot dapat trading di crypto market
- [ ] Bot dapat trading di forex market
- [ ] Demo mode uses virtual balance (isolated)
- [ ] Testnet uses real exchange (test credentials)
- [ ] Production uses live credentials

### Quality Criteria

- [ ] 80% unit test coverage
- [ ] 15% integration test coverage
- [ ] 5% feature test coverage
- [ ] Zero new type errors (PHPStan level 5)
- [ ] All existing tests pass (no regression)

### Performance Criteria

- [ ] Config hot-reload < 1 second
- [ ] Signal processing < 100ms
- [ ] Order execution < 500ms

---

## Dependencies

| Dependency | Type | Status |
|------------|------|--------|
| CCXT library | External | ✅ Existing |
| MetaApi SDK | External | ✅ Existing |
| Laravel 10 | Framework | ✅ Existing |
| PHPUnit | Testing | ✅ Existing |
| Mockery | Mocking | ✅ Existing |

---

## Risks and Mitigations

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Breaking existing bot configs | High | Low | Backward compatibility layer |
| Race conditions saat hot-reload | High | Medium | Atomic operations + locking |
| Forex market hours handling | Medium | Medium | MarketRouter with session awareness |
| Test coverage tidak tercapai | Medium | Low | Incremental approach + CI enforcement |

---

## Timeline

| Phase | Duration | Deliverables |
|-------|----------|--------------|
| Phase 1: Foundation | 8-12 hours | Test infrastructure, 10+ unit tests |
| Phase 2: Dynamic Config | 16-24 hours | ConfigManager, Redis pub/sub, WebSocket |
| Phase 3: Multi-Market | 20-30 hours | MarketRouter, SymbolNormalizer |
| Phase 4: Demo Mode | 16-24 hours | PaperTradingService, VirtualPortfolio |
| **Total** | **60-90 hours** | **Complete refactored trading bot** |

---

## Approval Required From

- [ ] Product Owner (requirements confirmation)
- [ ] Tech Lead (architecture approval)
- [ ] QA Lead (test strategy approval)

---

## Change Log

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 1.0 | 2026-01-19 | Sisyphus | Initial proposal |
