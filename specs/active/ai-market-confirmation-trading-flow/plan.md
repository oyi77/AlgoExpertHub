## Technical Plan: AI Market Confirmation Trading Flow

**Created:** 2025-12-02  
**Last Updated:** 2025-12-02  
**Status:** PLANNING  
**Version:** 0.1

---

### 1. Architecture Overview

Tujuan utama: menambahkan **pipeline eksekusi otomatis yang fully signal-centric** dengan tiga asset konfigurasi yang bisa di-setup/share oleh user:

- **Trading Preset (existing addon, extended)** – risk & execution profile.  
- **Filter Strategy (NEW addon)** – indikator & rule teknikal yang harus lolos sebelum trade.  
- **AI Model Profile (NEW addon/config)** – cara AI digunakan (confirm, scan, position management).

High-level flow target:

```text
Source (Telegram / AI Market Scan)
  → Filter Strategy (indikator)
  → AI Confirm / Generate (AI Model Profile)
  → Generate Signal (tetap pakai model Signal)
  → Eksekusi via Trading Preset + Execution Engine
  → AI Manage Open Position (TS / BE / SL+)
```

#### 1.1 Modul Utama

- **A. Trading Preset Addon (Existing, Extended)**
  - Sudah punya: risk per trade, multi-TP, BE, trailing, session, weekly target, dll.
  - Ditambah:
    - Field konfigurasi AI & filter:
      - `ai_confirmation_mode` (NONE / REQUIRED / ADVISORY).
      - `ai_min_safety_score`.
      - Referensi ke Filter Strategy & AI Model Profile (opsional).
    - Integrasi dengan AI decision engine untuk adjust lot (SIZE_DOWN, dsb).

- **B. Filter Strategy Addon (NEW)**
  - Menyimpan setup indikator & rule:
    - EMA, Stochastic, PSAR (v1), extensible ke indikator lain.
    - Rule logic: AND/OR, >, <, cross, dsb.
  - Evaluator service akan:
    - Ambil OHLCV.
    - Hitung indikator.
    - Evaluate rule → `pass` / `fail` (+ reason).

- **C. AI Model Profile & AI Decision Addon (NEW)**
  - Entity AI Model Profile:
    - Provider (OpenAI/Gemini/LLM lain), model, prompt template, mode:
      - `CONFIRM` – konfirmasi signal.
      - `SCAN` – pure market scan (generate trade).
      - `POSITION_MGMT` – manage posisi.
  - Services:
    - `MarketDataService` – ambil OHLCV + basic indikator.
    - `MarketAnalysisAiService` – wrapper ke LLM untuk:
      - Mode 1: confirm signal (alignment, safety_score, decision).
      - Mode 2: market scan (direction, entry/SL/TP).
    - `AiDecisionEngine` – gabungkan output AI + Trading Preset rule.
    - `AiPositionManagerService` – AI-driven trailing/BE/SL+ (layer di atas rule preset).

- **D. Monetization & Usage Tracking (Core / Addon)**
  - Counter per user/plan:
    - `ai_calls_per_month_limit`, `ai_calls_used`.
    - Optional: `ai_token_used`, `ai_cost_estimate`.
  - Per mode:
    - `cost_per_signal_confirmation`.
    - `cost_per_market_scan`.
    - `cost_per_position_management_cycle` (opsional).

---

### 2. Data Model & Schema Design (Conceptual)

> Catatan: nama tabel/field final harus dicocokkan dengan skema existing saat implementasi.

#### 2.1. Filter Strategy Addon

**Namespace:** `Addons\FilterStrategyAddon` (nama final bisa disesuaikan).  
**Tabel utama:** `filter_strategies`

Field utama (konseptual):

- Identity:
  - `id`, `name`, `description`.
  - `owner_id` (user), `visibility` (PRIVATE / PUBLIC_MARKETPLACE), `clonable`.
- Logic:
  - `config` (JSON) – daftar indikator, parameter, rule logic:
    - Contoh:
      - `indicators.ema_fast = { period: 10 }`.
      - `indicators.ema_slow = { period: 100 }`.
      - `indicators.stoch = { k: 14, d: 3, smooth: 3 }`.
      - `indicators.psar = { step: 0.02, max: 0.2 }`.
    - `rules`:
      - `logic` = `AND` / `OR`.
      - `conditions` = array of `left`, `operator`, `right` (e.g. `EMA10 > EMA100`).
- Meta:
  - `created_by_user_id`, timestamps, soft delete.

**Relasi / binding (logis):**

- FilterStrategy bisa direferensikan oleh:
  - `channel_sources` (per channel).
  - `execution_connections`.
  - `trading_presets`.
  - Entity lain (copy trading, bot) – via foreign key atau pivot.

Untuk fleksibilitas & backward compatibility, v1 cukup:

- Tambah kolom nullable:
  - `filter_strategy_id` di:
    - `channel_sources` (optional).
    - `execution_connections` (optional).
    - `trading_presets` (optional).

#### 2.2. AI Model Profile

**Namespace:** `Addons\AiTradingAddon` (atau serupa).  
**Tabel:** `ai_model_profiles`

Field utama:

- Identity:
  - `id`, `name`, `description`.
  - `owner_id` (user), `visibility`, `clonable`.
- Provider & model:
  - `provider` (openai, gemini, claude, local, dll).
  - `model_name`.
  - `api_key_ref` / reference ke config (bukan API key plain text!).
- Behavior:
  - `mode` ENUM: `CONFIRM`, `SCAN`, `POSITION_MGMT` (bisa multi-mode via JSON kalau perlu).
  - `prompt_template` (text) – gunakan placeholder untuk data market/signal.
  - `settings` JSON: temperature, max_tokens, dsb.
- Limits:
  - `max_calls_per_minute` (optional).
  - `max_calls_per_day` (optional).

**Binding:**

- Nullable `ai_model_profile_id` di:
  - `trading_presets` (per preset).
  - `execution_connections` / `channel_sources` (override jika perlu).

#### 2.3. Trading Preset Extension

Tambahan field di `trading_presets` (via addon trading-preset):

- AI & filter:
  - `filter_strategy_id` (nullable).
  - `ai_model_profile_id` (nullable).
  - `ai_confirmation_mode` ENUM: `NONE`, `REQUIRED`, `ADVISORY`.
  - `ai_min_safety_score` (decimal, 0–100).
  - Optional: `ai_position_mgmt_enabled` (bool).

Monetization bisa disimpan di:

- Tabel terpisah `ai_usage_stats`:
  - `user_id`, `mode`, `calls_used`, `calls_limit`, `period_start`, `period_end`.
  - Atau integrate ke existing plan/usage table kalau sudah ada.

---

### 3. Service Layer Design

#### 3.1. Market Data & Indicator Services

- **`MarketDataService`**
  - Tugas:
    - Ambil OHLCV dari exchange/broker via adapter (reuse dari Execution Engine bila memungkinkan).
    - Support multiple timeframes (M1, M5, M15, H1, dst).
  - API:
    - `getOhlcv(symbol, timeframe, limit = 100): Collection<Candle>`
    - `getLatestPrice(symbol): float`

- **`IndicatorService` / `IndicatorCalculator`**
  - Generic kalkulasi indikator dari OHLCV:
    - EMA, Stochastic, PSAR (v1).
  - API:
    - `calculateEma(candles, period)`.
    - `calculateStochastic(candles, k, d, smooth)`.
    - `calculatePsar(candles, step, max)`.

#### 3.2. Filter Strategy Evaluator

- **Namespace:** `Addons\FilterStrategyAddon\App\Services\FilterStrategyEvaluator`
- Input:
  - `FilterStrategy` entity.
  - Symbol / pair, timeframe.
  - Optional: pre-fetched candles (untuk menghindari double-fetch).
- Output:
  - `FilterResult` DTO:
    - `pass` (bool).
    - `reason` (string).
    - `indicators_snapshot` (optional, untuk logging/debug).
- Behavior:
  - Ambil market data via `MarketDataService`.
  - Hitung indikator via `IndicatorService`.
  - Evaluate rule logic dari `filter_strategy.config`.

**Integrasi di flow:**

- Hook setelah `ParsedSignalData` / sebelum AI confirm:
  - Ambil filter strategy ID dari:
    - Channel → Connection → Preset (prioritas final nanti diatur).
  - Jika ada:
    - Evaluate.
    - Kalau `pass = false` → mark `ChannelMessage` / `Signal` sebagai rejected (status/log), **tidak lanjut ke AI/eksekusi**.

#### 3.3. AI Market Analysis & Decision

- **`MarketAnalysisAiService`**
  - Input Mode 1 (Confirmation):
    - Market data (candles + indikator).
    - Signal context (direction, entry, SL, TP, timeframe).
    - `AiModelProfile` (mode = CONFIRM).
  - Output:
    - `alignment` (aligned / against / sideways).
    - `safety_score` (0–100).
    - `decision` (ACCEPT / REJECT / SIZE_DOWN / MANUAL_REVIEW).
    - Optional explanation text.
  - Input Mode 2 (Scan):
    - Market data.
    - `AiModelProfile` (mode = SCAN).
  - Output:
    - `should_open_trade` (bool).
    - `direction`, `entry_type`, `entry_price`, `sl`, `tp` (bisa multi-TP).
    - `confidence` / `safety_score`.

  - Implementasi:
    - Reuse pola `AiMessageParser` + `AiProviderFactory`:
      - Buat `AiTradingProviderFactory` untuk profil AI trading.
      - Provider-specific client yang tahu cara call LLM.

- **`AiDecisionEngine`**
  - Input:
    - Output dari `MarketAnalysisAiService`.
    - `TradingPreset` (rule: `ai_confirmation_mode`, `ai_min_safety_score`).
  - Output:
    - Final keputusan:
      - `execute` (bool).
      - `adjusted_risk_factor` (mis. 1.0 normal, 0.5 size down).
      - `reason`.
  - Logic contoh:
    - Jika `ai_confirmation_mode = NONE` → skip (selalu execute, AI opsional).
    - Jika REQUIRED:
      - Hanya execute kalau `decision = ACCEPT` dan `safety_score >= ai_min_safety_score`.
    - Jika ADVISORY:
      - Kalau `decision = SIZE_DOWN` → risk factor < 1.
      - Kalau `decision = REJECT` tapi `mode ADVISORY` → boleh skip atau tetap execute sesuai config lebih lanjut.

#### 3.4. AI Position Management

- **`AiPositionManagerService`**
  - Input:
    - Daftar `ExecutionPosition` open.
    - Market data per symbol.
    - AI model profile (mode = POSITION_MGMT).
    - Konfigurasi dari Trading Preset (BE/TS rule dasar).
  - Output per posisi:
    - Daftar action: adjust SL, move BE, tighten TP, partial close, full close.
  - Jalur eksekusi:
    - Cron job periodik (misal setiap 1–5 menit).
    - Atau scheduler existing Execution Engine (kalau sudah ada job monitor).
  - Di v1 bisa dibuat minimal:
    - Mulai dari rekomendasi sederhana (mis. adjust SL) dan masih respect rule preset yang sudah ada.

---

### 4. Execution Flow Integration

#### 4.1. Telegram Flow (Signal Confirmation)

1. Pesan masuk → `ChannelMessage` (existing).  
2. `ProcessChannelMessage` → `ParsingPipeline` (regex + `AiMessageParser`) → `ParsedSignalData`.  
3. `AutoSignalService` → buat `Signal` (draft / auto_created).  
4. **Filter Strategy Layer (NEW):**
   - Resolusi filter:
     - Order prioritas (draft): Preset > Connection > Channel (bisa disesuaikan).
   - `FilterStrategyEvaluator`:
     - Jika `fail` → mark message/signal rejected, stop.
5. **AI Confirm Layer (NEW):**
   - Market data + indikator via `MarketDataService` + `IndicatorService`.
   - `MarketAnalysisAiService` (mode CONFIRM) → hasil.  
   - `AiDecisionEngine` + Trading Preset:
     - Keputusan execute / skip / size down.
6. **Generate / Publish Signal:**
   - Kalau execute:
     - Pastikan `Signal` dipublish (existing logic).
7. **Execution Engine (Existing + Preset Extension):**
   - `SignalPublishedListener` → `SignalExecutionService`.
   - `TradingPreset` + hasil `AiDecisionEngine` (risk factor) → position size & eksekusi.

#### 4.2. AI Market Scan Flow (Generate Internal Signal)

1. Cron job `RunMarketScanJob`:
   - Loop pair + timeframe + preset/filter/AI profile terkait.
2. Market data fetch → `MarketDataService`.  
3. Filter Strategy (opsional, mis. kondisi baseline trend):  
   - `FilterStrategyEvaluator` → ensure market in kondisi yang diinginkan.
4. AI Scan:
   - `MarketAnalysisAiService` (mode SCAN) → rekomendasi trade.
   - `AiDecisionEngine` + preset rule:
     - Cek `should_open_trade`, `confidence`, `risk factor`, dsb.
5. Jika boleh trade:
   - Buat `Signal` baru:
     - `auto_created = 1`, `channel_source_id` khusus (AI Bot).
   - Attach ke plan / user scope sesuai desain (plan khusus AI).
6. Publish signal + eksekusi seperti flow biasa.

#### 4.3. AI Position Management Flow

1. Cron job `RunAiPositionManagementJob`:
   - Iterasi semua `ExecutionPosition` open yang:
     - Punya preset dengan `ai_position_mgmt_enabled = true` atau AI profile terbinding.
2. Market data fetch per symbol → `MarketDataService`.  
3. `AiPositionManagerService`:
   - Gunakan AI profile mode `POSITION_MGMT` + rule preset.
   - Hasil: action list (adjust SL, TP, partial close, dsb).
4. Jalankan action via Execution Engine:
   - Adapter-specific calls (modify order/position).

---

### 5. Security & Safety Considerations

- **AI as advisor, preset as guardrail:**
  - Semua AI decision harus dibatasi oleh:
    - Max risk per trade dari preset.
    - Weekly target, session, max positions, dsb.
- **Fail-safe default:**
  - Kalau Filter Strategy error / MarketData gagal / AI error:
    - **Default = tidak eksekusi trade** (lebih aman).
  - Jangan pernah fallback ke “asal eksekusi saja” ketika AI/Filter fail.
- **Credentials & API keys:**
  - AI provider credentials disimpan encrypted (mengikuti aturan gateway & config).
  - Tidak pernah log API key / secret.
- **Usage limits:**
  - Hard stop kalau usage melewati limit:
    - Non-critical path: kirim notif ke user/admin.
    - Trading tetap jalan dengan preset + filter non-AI (kalau masih diizinkan).

---

### 6. Performance Considerations

- **Market data reuse:**
  - Untuk Telegram flow: reuse OHLCV dan indikator antara Filter Strategy & AI confirm (jangan fetch dua kali).
- **Batching:**
  - Market scan & position management:
    - Batch per symbol, bukan per posisi, untuk efisiensi.
- **Queue:**
  - Panggilan AI dan evaluasi indikator berjalan di queue jobs:
    - Hindari blocking request utama.
  - Pastikan job gagal tidak menyebabkan trade liar (fail-safe).

---

### 7. Testing Strategy (High-Level)

- **Unit tests:**
  - `FilterStrategyEvaluator` – berbagai kombinasi indikator & rule.
  - `MarketDataService` – integrasi dengan adapter mock.
  - `AiDecisionEngine` – semua kombinasi `ai_confirmation_mode` dan keputusan AI.
- **Integration tests:**
  - Telegram → Filter → AI Confirm → Execution (end-to-end dengan mocks AI & market data).
  - Market Scan → AI → Signal → Execution.
  - Position Management → adjustment orders.
- **Load tests:**
  - Banyak channel & preset aktif:
    - Pastikan cron & queue tidak overload.

---

### 8. Implementation Phasing (Outline)

> Detail per-task akan ada di `tasks.md`.

1. **Phase 1 – Foundation & Models**
   - Buat addon Filter Strategy (model, migration, basic CRUD).
   - Buat addon / module AI Model Profile (model, migration, basic CRUD).
   - Extend Trading Preset dengan field AI/filter (schema).
2. **Phase 2 – Market Data & Indicator Layer**
   - Implement `MarketDataService` + `IndicatorService`.
3. **Phase 3 – Filter Strategy Evaluator**
   - Implement evaluator + hook sederhana (tanpa AI).
4. **Phase 4 – AI Model Integration**
   - Implement `AiTradingProviderFactory`, `MarketAnalysisAiService`, `AiDecisionEngine`.
5. **Phase 5 – Telegram Flow Integration**
   - Hook Filter Strategy + AI Confirm ke pipeline autopublish sebelum eksekusi.
6. **Phase 6 – Market Scan Flow**
   - Cron job scan + generate Signal internal.
7. **Phase 7 – AI Position Management (Optional v1.1)**
   - Position manager + cron.
8. **Phase 8 – Usage Tracking & Monetization**
   - Counter per user/plan + limit enforcement.

---

### 9. Risks & Open Questions

- **Risk: Over-complexity on v1**
  - Mitigasi: mulai dari:
    - 1–2 indikator (EMA/Stoch).
    - 1–2 AI mode (CONFIRM + SCAN).
    - Position management AI bisa di-scope ke v1.1.
- **Open questions (but can be deferred):**
  - Bagaimana mapping preset/filter/AI profile terbaik (per channel vs per connection vs per preset)?
  - Apakah AI Position Management wajib di release pertama atau cukup non-AI BE/TS dari preset dulu?

---

### 10. Additional Requirements & Safety Considerations

#### 10.1. Kontrak Penyimpanan Hasil Filter & AI

**Prinsip:** Hasil evaluasi Filter Strategy dan analisa AI **tidak wajib** field baru di tabel utama. Gunakan pola yang sudah ada di sistem:

- **Opsi 1: Metadata JSON di Model Existing**
  - `Signal`: Tambah field `metadata` JSON (jika belum ada) atau gunakan field existing yang bisa di-extend.
  - `ExecutionLog`: Gunakan field `response_data` JSON yang sudah ada untuk menyimpan hasil AI decision.
  - `ChannelMessage`: Field `parsed_data` JSON bisa di-extend untuk menyimpan hasil filter evaluation.

- **Opsi 2: Tabel Log Terpisah (Jika Perlu Audit Trail Lebih Detail)**
  - Tabel baru: `ai_trade_analyses` atau `filter_evaluation_logs`:
    - `signal_id`, `channel_message_id`, `connection_id` (nullable).
    - `filter_strategy_id`, `filter_result` (pass/fail), `filter_reason` (text).
    - `ai_model_profile_id`, `ai_result` (JSON: alignment, safety_score, decision).
    - `evaluated_at`, `metadata` (JSON).

**Rekomendasi:** Mulai dengan **Opsi 1** (metadata JSON) untuk MVP. Jika butuh query/analytics lebih detail, tambahkan tabel log terpisah di iterasi berikutnya.

**Catatan Penting:** Selalu mapping ke struktur real yang sudah ada. Jangan asal bikin field baru tanpa cek apakah ada pola metadata/log yang bisa dipakai.

#### 10.2. Fail-Safe & Error Handling

**Prinsip:** **Lebih baik lost opportunity daripada lost capital.**

- **Default Behavior pada Error:**
  - Jika `MarketDataService` error (timeout, API down, invalid symbol):
    - **TIDAK eksekusi trade baru.**
    - Log error dengan context lengkap.
    - Signal tetap bisa dipublish (untuk notifikasi user), tapi execution di-skip.
  
  - Jika `IndicatorService` error (data tidak cukup, kalkulasi gagal):
    - **TIDAK eksekusi trade baru.**
    - Filter Strategy evaluator return `fail` dengan reason.
  
  - Jika `MarketAnalysisAiService` error (API limit, timeout, invalid response):
    - **TIDAK eksekusi trade baru.**
    - Jika `ai_confirmation_mode = REQUIRED` → skip execution.
    - Jika `ai_confirmation_mode = ADVISORY` → bisa lanjut tanpa AI (opsional, configurable).
    - Jika `ai_confirmation_mode = NONE` → tidak panggil AI, lanjut normal.
  
  - Jika AI usage limit tercapai (user/plan limit):
    - **TIDAK eksekusi trade baru** (jika mode = REQUIRED).
    - Log warning, notify user/admin jika perlu.

- **Execution Engine Tetap Berjalan untuk Posisi Existing:**
  - Error di Filter/AI **hanya memblokir trade baru**.
  - Closing posisi (SL/TP hit, manual close) tetap jalan normal.
  - Trailing stop, break-even, position management dari preset tetap aktif.

- **Error Handling Pattern:**
  ```php
  try {
      $filterResult = $filterEvaluator->evaluate($strategy, $signal);
      if (!$filterResult->pass) {
          // Log & stop, jangan eksekusi
          return ['execute' => false, 'reason' => $filterResult->reason];
      }
  } catch (\Exception $e) {
      // Fail-safe: tidak eksekusi
      Log::error('Filter evaluation failed', ['error' => $e->getMessage()]);
      return ['execute' => false, 'reason' => 'Filter evaluation error'];
  }
  ```

#### 10.3. Idempotensi & Anti Double-Execution

**Prinsip:** Setiap kombinasi `signal_id + connection_id + direction` hanya boleh dieksekusi **sekali**, walaupun:
- Job AI/filter dipanggil dua kali (retry, duplicate dispatch).
- Listener ke-trigger dua kali (event duplicate).
- User manual trigger eksekusi dua kali.

**Implementasi:**

- **Idempotency Key:**
  - Format: `signal_{signal_id}_conn_{connection_id}_side_{direction}`.
  - Atau hash: `md5("signal:{$signal_id}:conn:{$connection_id}:side:{$direction}")`.
  
- **Storage:**
  - Opsi 1: Cache (Redis/Memcached) dengan TTL 1 jam.
  - Opsi 2: Database table `execution_idempotency_keys`:
    - `idempotency_key` (string, unique), `signal_id`, `connection_id`, `direction`, `created_at`.
    - Cleanup old keys via cron (older than 24 hours).

- **Check Pattern:**
  ```php
  $idempotencyKey = "signal:{$signal->id}:conn:{$connection->id}:side:{$signal->direction}";
  
  if (Cache::has($idempotencyKey)) {
      // Already executed or in progress
      return ['execute' => false, 'reason' => 'Duplicate execution prevented'];
  }
  
  // Mark as in progress
  Cache::put($idempotencyKey, true, 3600); // 1 hour TTL
  
  try {
      // Execute trade...
      $result = $executionService->execute($signal, $connection);
      
      // Mark as completed (extend TTL)
      Cache::put($idempotencyKey, true, 86400); // 24 hours
      
      return $result;
  } catch (\Exception $e) {
      // On error, remove key so retry is possible
      Cache::forget($idempotencyKey);
      throw $e;
  }
  ```

- **Integration Point:**
  - Di `SignalExecutionService::executeSignal()` atau di listener sebelum eksekusi.

#### 10.4. Feature Flag / Scoping Awal

**Prinsip:** Fitur AI + Filter **tidak langsung aktif** untuk semua signal. Rollout gradual dengan feature flag.

- **MVP Scope (Sprint 1):**
  - Aktifkan hanya untuk:
    - **Channel tertentu** yang ditandai (mis. `channel_sources.enable_filter = 1`, `channel_sources.enable_ai_confirm = 1`).
    - **Preset tertentu** yang sudah di-setup dengan Filter Strategy & AI Profile.
    - **Environment staging** dulu, baru production setelah verified.
  
- **Feature Flags (Database/Config):**
  - Di `channel_sources` table:
    - `filter_strategy_enabled` (boolean, default 0).
    - `ai_confirmation_enabled` (boolean, default 0).
  - Atau di config addon:
    - `filter_strategy.enabled_channels` (array of channel IDs).
    - `ai_confirmation.enabled_channels` (array of channel IDs).
  
- **Rollout Strategy:**
  1. **Phase 1:** 1 channel + 1 preset (internal testing).
  2. **Phase 2:** 2–3 channels + beberapa preset (beta users).
  3. **Phase 3:** Semua channels (production rollout).

- **Monitoring:**
  - Track: jumlah signal yang di-filter, jumlah yang di-reject AI, error rate.
  - Alert jika error rate > threshold atau banyak false reject.

#### 10.5. Migration & Backward Compatibility

**Prinsip:** Semua perubahan schema **harus backward compatible**. Tidak ada breaking changes di v1.

- **Kolom Baru:**
  - Semua kolom baru harus **nullable**:
    - `trading_presets.filter_strategy_id` → nullable.
    - `trading_presets.ai_model_profile_id` → nullable.
    - `trading_presets.ai_confirmation_mode` → nullable atau default `'NONE'`.
    - `trading_presets.ai_min_safety_score` → nullable.
    - `channel_sources.filter_strategy_enabled` → default `0` (false).
    - `channel_sources.ai_confirmation_enabled` → default `0` (false).
  
- **Default Behavior:**
  - Jika `filter_strategy_id = null` → Filter Strategy tidak dijalankan (bypass, behavior lama).
  - Jika `ai_confirmation_mode = null` atau `'NONE'` → AI tidak dipanggil (behavior lama).
  - Jika `ai_min_safety_score = null` → Tidak ada threshold (behavior lama).
  
- **Migration Rules:**
  - **TIDAK ada** migration yang:
    - DROP kolom existing.
    - Mengubah tipe data kolom existing (kecuali safe cast, e.g. varchar → text).
    - Mengubah makna/constraint kolom existing.
  - Semua migration harus **reversible** (down() method lengkap).
  
- **Data Migration (Jika Perlu):**
  - Jika ada data existing yang perlu di-migrate:
    - Buat migration terpisah (bukan di create table migration).
    - Test dengan data production copy dulu.
    - Rollback plan jelas.

- **Testing Backward Compatibility:**
  - Test bahwa:
    - Signal tanpa preset (null filter_strategy_id) tetap jalan normal.
    - Channel tanpa flag enabled tetap jalan normal (tanpa filter/AI).
    - Execution engine tanpa preset AI tetap jalan normal.

---

**Next Step:** Implementasikan breakdown tasks detail di `tasks.md` dan pilih subset fase untuk MVP pertama (disarankan: Filter Strategy + AI Confirm untuk Telegram 1 channel + 1 preset). 


