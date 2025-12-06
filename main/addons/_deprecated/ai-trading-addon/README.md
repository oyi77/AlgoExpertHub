# AI Trading Addon

**Version:** 1.0.0  
**Status:** Active  
**Sprint:** 2 (AI Market Confirmation fully working)

## Overview

AI Trading Addon provides AI-powered market analysis, signal confirmation, and position management for trading signals. Signals are analyzed by AI before execution to ensure safety and alignment with market conditions.

## Features (Sprint 2)

- ✅ AI Model Profile CRUD (Create, Read, Update, Delete)
- ✅ AI Provider Integration (OpenAI, Gemini)
- ✅ Market Analysis AI Service (CONFIRM & SCAN modes)
- ✅ AI Decision Engine (combines AI output with preset rules)
- ✅ Integration with Trading Preset
- ✅ Hook to Telegram signal flow
- ✅ Execution integration (position size adjustment)
- ✅ Observability & Debug UI (Admin decision logs)
- ✅ Fail-safe error handling

## Architecture

### Components

1. **AiModelProfile Model** - Stores AI model configurations
2. **AiTradingProviderFactory** - Factory for creating AI provider instances
3. **OpenAiTradingProvider & GeminiTradingProvider** - Provider implementations
4. **MarketAnalysisAiService** - Service for AI analysis (CONFIRM & SCAN)
5. **AiDecisionEngine** - Combines AI output with preset rules
6. **AiDecisionLogController** - Admin observability interface

### Flow

```
Telegram Message
  → ParsingPipeline
  → AutoSignalService::createFromParsedData()
  → FilterStrategyEvaluator (Sprint 1)
  → MarketAnalysisAiService::confirmSignal() (Sprint 2)
  → AiDecisionEngine::makeDecision()
  → If PASS: Continue to auto-publish
  → If FAIL: Stop, signal not published
  → ExecuteSignalJob
  → SignalExecutionService (applies adjusted_risk_factor)
```

## Database Schema

### ai_model_profiles

- `id` - Primary key
- `name` - Profile name
- `description` - Description
- `created_by_user_id` - Owner (nullable)
- `visibility` - PRIVATE / PUBLIC_MARKETPLACE
- `clonable` - Allow cloning
- `enabled` - Enable/disable
- `provider` - openai, gemini, etc.
- `model_name` - e.g., gpt-4, gemini-pro
- `api_key_ref` - Reference to config/env (not plain text)
- `mode` - CONFIRM, SCAN, POSITION_MGMT
- `prompt_template` - Template with placeholders
- `settings` - JSON (temperature, max_tokens, etc.)
- `max_calls_per_minute` - Optional limit
- `max_calls_per_day` - Optional limit
- `created_at`, `updated_at`, `deleted_at`

### trading_presets (Extended)

- `ai_model_profile_id` - Foreign key to ai_model_profiles (nullable)
- `ai_confirmation_mode` - NONE, REQUIRED, ADVISORY
- `ai_min_safety_score` - Minimum safety score (0-100, nullable)
- `ai_position_mgmt_enabled` - Enable AI position management (boolean)

## AI Confirmation Modes

### NONE
- AI confirmation disabled
- Signals execute normally (if filter passes)

### REQUIRED
- AI confirmation is mandatory
- If AI rejects → signal not executed
- If AI accepts → signal executed
- If AI recommends SIZE_DOWN → position size reduced

### ADVISORY
- AI confirmation is advisory only
- If AI rejects → signal can still execute (with reduced risk)
- If AI accepts → signal executed normally
- If AI recommends SIZE_DOWN → position size reduced

## Usage

### Creating an AI Model Profile

1. Go to `/user/ai-model-profiles/create`
2. Enter name, description
3. Select provider (OpenAI, Gemini)
4. Enter model name (e.g., gpt-4, gemini-pro)
5. Set API key reference (env variable name)
6. Select mode (CONFIRM, SCAN, POSITION_MGMT)
7. Enter prompt template (with placeholders)
8. Configure settings (JSON)
9. Set visibility and enable status
10. Save

### Assigning to Trading Preset

1. Edit a Trading Preset
2. In Basic tab, select AI Model Profile from dropdown
3. Configure AI settings:
   - AI Confirmation Mode (NONE/REQUIRED/ADVISORY)
   - Min Safety Score (optional)
   - Enable AI Position Management (optional)
4. Save preset

### How It Works

When a Telegram signal is received:
1. Signal is parsed and created (draft)
2. Filter Strategy is evaluated (Sprint 1)
3. If filter passes → AI Model Profile is resolved from preset
4. If AI profile exists → Market data is fetched
5. AI analyzes signal + market data
6. AiDecisionEngine makes final decision based on:
   - AI output (alignment, safety_score, decision)
   - Preset rules (ai_confirmation_mode, ai_min_safety_score)
7. If AI decision is EXECUTE → signal can be published
8. If AI decision is REJECT → signal not published
9. If AI recommends SIZE_DOWN → adjusted_risk_factor applied to position size

## Fail-Safe Behavior

- If MarketAnalysisAiService fails → AI evaluation fails → Signal not published (REQUIRED mode) or published with warning (ADVISORY mode)
- If AI provider API fails → AI evaluation fails → Signal not published (REQUIRED mode)
- If AiDecisionEngine throws exception → Signal not published
- **Default: Reject on error** (safer than allowing through)

## Results Storage

AI evaluation results are stored in:
- `channel_messages.parsed_data['ai_evaluation']` - JSON field

Structure:
```json
{
  "execute": true/false,
  "adjusted_risk_factor": 0.0-1.0,
  "reason": "string",
  "profile_id": 123,
  "ai_result": {
    "alignment": 0-100,
    "safety_score": 0-100,
    "decision": "ACCEPT|REJECT|SIZE_DOWN",
    "reasoning": "string",
    "confidence": 0-100
  }
}
```

## Execution Integration

When signal is executed:
1. `ExecuteSignalJob` retrieves AI decision from `channel_message.parsed_data`
2. If `execute = false` → execution skipped
3. If `adjusted_risk_factor < 1.0` → position size multiplied by risk factor
4. Example: Base size 0.1 lot, risk factor 0.5 → Final size 0.05 lot

## Routes

### Admin
- `GET /admin/ai-model-profiles` - List all profiles
- `GET /admin/ai-model-profiles/create` - Create form
- `POST /admin/ai-model-profiles` - Store
- `GET /admin/ai-model-profiles/{id}` - Show
- `GET /admin/ai-model-profiles/{id}/edit` - Edit form
- `PUT /admin/ai-model-profiles/{id}` - Update
- `DELETE /admin/ai-model-profiles/{id}` - Delete
- `GET /admin/ai-decision-logs` - Decision logs (observability)
- `GET /admin/ai-decision-logs/{id}` - Log details

### User
- `GET /user/ai-model-profiles` - List own + public profiles
- `GET /user/ai-model-profiles/marketplace` - Browse public profiles
- `GET /user/ai-model-profiles/create` - Create form
- `POST /user/ai-model-profiles` - Store
- `GET /user/ai-model-profiles/{id}` - Show
- `GET /user/ai-model-profiles/{id}/edit` - Edit form
- `PUT /user/ai-model-profiles/{id}` - Update
- `DELETE /user/ai-model-profiles/{id}` - Delete
- `POST /user/ai-model-profiles/{id}/clone` - Clone profile

## Observability

Admin can view AI & Filter decision logs at `/admin/ai-decision-logs`:
- Filter by date range
- Filter by filter result (pass/fail)
- Filter by AI decision (accept/reject)
- View detailed analysis for each signal
- See indicators, AI reasoning, and final decisions

## Next Steps (Future Sprints)

- AI Market Scan (periodic market analysis)
- AI Position Management (trailing stop, break-even adjustments)
- Usage tracking & monetization (AI call limits per plan)
- Advanced prompt templates with more placeholders
- Multi-mode AI profiles (CONFIRM + SCAN + POSITION_MGMT)

