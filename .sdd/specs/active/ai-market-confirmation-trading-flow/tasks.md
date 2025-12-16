## Tasks: AI Market Confirmation Trading Flow

**Created:** 2025-12-02  
**Last Updated:** 2025-12-02  
**Status:** Draft  

> Catatan: Estimasi kasar, bisa dipecah/diubah sesuai kapasitas tim. Fokus MVP disarankan:  
> - Filter Strategy + AI Confirm di Telegram flow untuk 1–2 channel + 1 preset.

---

### Legend

- **Priority:** HIGH / MEDIUM / LOW  
- **Estimate:** per task (ideal time, bukan kalender)  
- **Deps:** dependency utama (id task lain)

---

### Phase 1 – Foundation & Data Model (Filter Strategy + AI Profile + Preset Extension)

#### Task 1.1 – Create Filter Strategy Addon Skeleton
- **Priority:** HIGH  
- **Estimate:** 0.5–1 hari  
- **Description:**  
  - Buat addon baru, mis. `filter-strategy-addon`, mengikuti pola addon lain (addon.json, ServiceProvider, routes skeleton).
- **Acceptance Criteria:**  
  - Addon ter-registrasi dan bisa di-enable/disable via sistem addon.
  - ServiceProvider memuat migration & views (walau kosong).

#### Task 1.2 – FilterStrategy Model & Migration
- **Priority:** HIGH  
- **Estimate:** 1 hari  
- **Description:**  
  - Buat model `FilterStrategy` + migration `filter_strategies` dengan field:
    - Identity: name, description, owner, visibility, clonable.
    - `config` JSON (indikator + rules).
  - Tambah relasi ke `User`.
- **Acceptance Criteria:**  
  - Migration jalan sukses.
  - Model bisa create/read basic data di Tinker/test.

#### Task 1.3 – AI Model Profile Model & Migration
- **Priority:** HIGH  
- **Estimate:** 1 hari  
- **Description:**  
  - Buat addon/module untuk AI Profile (boleh di addon baru, mis. `ai-trading-addon`, atau bagian dari filter/exec).
  - Model `AiModelProfile` + migration:
    - name, description, owner, visibility, clonable.
    - provider, model_name, prompt_template, mode, settings JSON.
- **Acceptance Criteria:**  
  - Migration sukses.
  - Bisa simpan beberapa profile AI dummy.

#### Task 1.4 – Extend TradingPreset Schema dengan AI & Filter Fields
- **Priority:** HIGH  
- **Estimate:** 0.5–1 hari  
- **Description:**  
  - Tambah kolom di `trading_presets`:
    - `filter_strategy_id` (nullable, FK).
    - `ai_model_profile_id` (nullable, FK).
    - `ai_confirmation_mode` (NONE / REQUIRED / ADVISORY).
    - `ai_min_safety_score` (decimal).
    - `ai_position_mgmt_enabled` (bool, optional).
- **Acceptance Criteria:**  
  - Migration ok, index & FK benar.
  - Preset existing masih load normal (backward compatible).

#### Task 1.5 – Bindings & Basic Repository/Service
- **Priority:** MEDIUM  
- **Estimate:** 1 hari  
- **Description:**  
  - Tambah relasi:
    - `TradingPreset->filterStrategy()`, `TradingPreset->aiModelProfile()`.
    - `FilterStrategy->owner()`, `AiModelProfile->owner()`.
  - Siapkan service dasar (mis. `FilterStrategyService`, `AiModelProfileService`) untuk CRUD.
- **Acceptance Criteria:**  
  - Relasi Eloquent berfungsi di test sederhana.
  - Service bisa create/update/delete entity dasar.

---

### Phase 2 – Market Data & Indicator Layer

#### Task 2.1 – Implement MarketDataService
- **Priority:** HIGH  
- **Estimate:** 1–1.5 hari  
- **Description:**  
  - Service (di addon AI / execution engine) untuk ambil OHLCV & harga terakhir:
    - Integrasi pertama ke 1 adapter exchange/broker yang sudah ada.
  - API:
    - `getOhlcv(symbol, timeframe, limit)`  
    - `getLatestPrice(symbol)`
- **Acceptance Criteria:**  
  - Unit test dengan adapter mock.
  - Minimal satu exchange nyata bisa di-call di environment dev/test (kalau tersedia).

#### Task 2.2 – IndicatorService (EMA, Stochastic, PSAR)
- **Priority:** HIGH  
- **Estimate:** 1–1.5 hari  
- **Description:**  
  - Implement kalkulasi EMA, Stochastic, PSAR dari OHLCV.
  - API sederhana untuk dipakai Filter Strategy & AI service.
- **Acceptance Criteria:**  
  - Unit tests dengan data dummy (cek nilai indikator vs expected).

---

### Phase 3 – Filter Strategy Evaluator & UI Minimum

#### Task 3.1 – FilterStrategyEvaluator Service
- **Priority:** HIGH  
- **Estimate:** 1–1.5 hari  
- **Description:**  
  - Implement service:
    - Input: FilterStrategy, symbol/pair, timeframe (+ optional pre-fetched candles).
    - Output: `pass`/`fail`, reason, indikator snapshot.
  - Parse `config` JSON jadi rules, evaluasi dengan indikator dari Task 2.2.
- **Acceptance Criteria:**  
  - Unit tests untuk beberapa kombinasi rule:
    - Example: `EMA10 > EMA100 AND Stoch < 80 AND PSAR bawah harga`.

#### Task 3.2 – Minimal Admin/User UI untuk FilterStrategy
- **Priority:** MEDIUM  
- **Estimate:** 2–3 hari  
- **Description:**  
  - Buat UI minimal (bisa JSON editor dulu, tanpa builder kompleks):
    - Admin/User bisa:
      - Create/Edit FilterStrategy (name, description, config JSON).
      - Clone & set visibility (PRIVATE/PUBLIC).
  - Integrasi ke layout existing (admin & user).
- **Acceptance Criteria:**  
  - Flow CRUD dasar untuk FilterStrategy berfungsi.
  - Visibility & owner terhormat (user hanya bisa edit miliknya).

#### Task 3.3 – Bind FilterStrategy ke TradingPreset &/or Connection
- **Priority:** MEDIUM  
- **Estimate:** 1 hari  
- **Description:**  
  - Di UI Trading Preset (addon existing), tambah:
    - Dropdown pilih `filter_strategy_id` (hanya milik user + public).
  - Optional v1: binding di `execution_connections` kalau dibutuhkan segera.
- **Acceptance Criteria:**  
  - User bisa memilih FilterStrategy untuk preset tertentu.
  - Disimpan dengan benar di DB.

---

### Phase 4 – AI Model Profile & AI Service Integration

#### Task 4.1 – AI Provider Integration Skeleton (AiTradingProviderFactory)
- **Priority:** HIGH  
- **Estimate:** 1–2 hari  
- **Description:**  
  - Buat `AiTradingProviderFactory` mirip `AiProviderFactory` di Multi-Channel addon:
    - Support minimal 1 provider (mis. OpenAI/Gemini) via config.
  - Abstraksi interface:
    - `analyzeForConfirmation(...)`.
    - `analyzeForScan(...)`.
    - `analyzeForPositionMgmt(...)`.
- **Acceptance Criteria:**  
  - Bisa panggil AI dummy (atau live) dengan prompt template sederhana.
  - Error handling + logging dasar.

#### Task 4.2 – MarketAnalysisAiService (CONFIRM & SCAN mode)
- **Priority:** HIGH  
- **Estimate:** 2 hari  
- **Description:**  
  - Implement service:
    - Mode CONFIRM:
      - Input: signal + market data.
      - Output: alignment, safety_score, decision (ACCEPT/REJECT/SIZE_DOWN/...).
    - Mode SCAN:
      - Input: market data.
      - Output: should_open_trade, direction, entry/SL/TP, confidence.
  - Gunakan `AiModelProfile` untuk pilih provider/model/prompt.
- **Acceptance Criteria:**  
  - Unit tests dengan provider mock.
  - Struktur output konsisten (DTO).

#### Task 4.3 – AiDecisionEngine
- **Priority:** HIGH  
- **Estimate:** 1–1.5 hari  
- **Description:**  
  - Implement engine yang menggabungkan:
    - Output AI CONFIRM/SCAN.
    - Rule dari TradingPreset (`ai_confirmation_mode`, `ai_min_safety_score`).
  - Output:
    - execute (bool), adjusted_risk_factor, reason.
- **Acceptance Criteria:**  
  - Unit tests untuk semua kombinasi utama:
    - Mode NONE/REQUIRED/ADVISORY.
    - safety_score di bawah/atas threshold.
    - decision = ACCEPT/REJECT/SIZE_DOWN.

#### Task 4.4 – Minimal UI untuk AI Model Profile
- **Priority:** MEDIUM  
- **Estimate:** 2 hari  
- **Description:**  
  - Admin/User bisa:
    - Buat profile AI (name, provider, model, mode, prompt_template).
    - Clone & set visibility (marketplace-ready).
  - Integrasi ke TradingPreset (dropdown pilih AI profile).
- **Acceptance Criteria:**  
  - CRUD AI Model Profile dasar berjalan.
  - Preset bisa direlasikan ke AI profile.

---

### Phase 5 – Telegram Flow Integration (MVP Eksekusi Nyata)

#### Task 5.1 – Hook FilterStrategyEvaluator ke ProcessChannelMessage Flow
- **Priority:** HIGH  
- **Estimate:** 1–1.5 hari  
- **Description:**  
  - Di Multi-Channel addon:
    - Setelah `ParsedSignalData` dibuat & `Signal` draft terbentuk:
      - Resolusi FilterStrategy (prioritas: preset > connection > channel).
      - Panggil `FilterStrategyEvaluator`.
      - Kalau `pass = false`:
        - Update `ChannelMessage` / `Signal` status (e.g. `rejected_by_filter`).
        - Stop sebelum AI & eksekusi.
- **Acceptance Criteria:**  
  - Signal yang tidak memenuhi indikator **tidak** pernah dieksekusi.
  - Logging reason tersedia (untuk debugging).

#### Task 5.2 – Hook MarketAnalysisAiService (CONFIRM) ke Telegram Flow
- **Priority:** HIGH  
- **Estimate:** 2 hari  
- **Description:**  
  - Setelah FilterStrategy pass:
    - Kumpulkan market data untuk pair/timeframe signal.
    - Panggil `MarketAnalysisAiService` (CONFIRM) + `AiDecisionEngine` sesuai preset.
    - Simpan hasil AI ke log/metadata signal.
  - Pastikan error AI → default = tidak eksekusi.
- **Acceptance Criteria:**  
  - Untuk channel + preset yang diaktifkan:
    - Tiap signal Telegram punya record AI confirm (sukses atau fail + reason).

#### Task 5.3 – Integrasi Keputusan AI ke Execution Engine (TradingPreset)
- **Priority:** HIGH  
- **Estimate:** 1–2 hari  
- **Description:**  
  - Di `SignalExecutionService` / resolver preset:
    - Ambil hasil `AiDecisionEngine` (mis. dari metadata signal atau external store).
    - Kalau `execute = false` → skip eksekusi (log reason).
    - Kalau `adjusted_risk_factor < 1` → scale-down position size.
- **Acceptance Criteria:**  
  - Signal yang di-REJECT AI tidak dieksekusi.
  - SIZE_DOWN benar-benar mengurangi lot (test case).

#### Task 5.4 – End-to-End Test: Telegram Channel → Filter → AI Confirm → Order
- **Priority:** HIGH  
- **Estimate:** 1 hari  
- **Description:**  
  - Buat integration test / manual test scenario:
    - Skenario pass:
      - Pesan Telegram valid → Filter pass → AI accept → order jalan.
    - Skenario fail filter:
      - Indikator tidak sesuai → tidak ada order.
    - Skenario AI reject:
      - AI menghasilkan REJECT → tidak ada order.
- **Acceptance Criteria:**  
  - Semua skenario tertutup & terverifikasi di staging/dev.

---

### Phase 6 – AI Market Scan Flow

#### Task 6.1 – Scheduler Job RunMarketScanJob
- **Priority:** MEDIUM  
- **Estimate:** 1–1.5 hari  
- **Description:**  
  - Job di addon AI Trading:
    - Loop pair/timeframe/preset/AI profile yang di-config.
    - Ambil market data via `MarketDataService`.
    - (Opsional) jalankan FilterStrategy dulu sebagai baseline.
    - Panggil `MarketAnalysisAiService` (SCAN) + `AiDecisionEngine`.
- **Acceptance Criteria:**  
  - Job bisa dijalankan manual `php artisan` dan log hasil analisa (tanpa eksekusi dulu).

#### Task 6.2 – Generate Internal Signal dari AI Market Scan
- **Priority:** MEDIUM  
- **Estimate:** 1–1.5 hari  
- **Description:**  
  - Kalau `should_open_trade = true` dan keputusan final ok:
    - Buat `Signal` internal:
      - `auto_created = 1`.
      - `channel_source_id` khusus AI Bot.
    - Attach ke plan/preset yang sesuai.
- **Acceptance Criteria:**  
  - Signal muncul di DB & dashboard admin sebagai “AI Bot Signal”.

#### Task 6.3 – Wire ke Execution Engine via Existing Publish Flow
- **Priority:** MEDIUM  
- **Estimate:** 1 hari  
- **Description:**  
  - Publish signal AI (bisa auto-publish).
  - Pastikan Execution Engine + Preset logic sama seperti signal biasa.
- **Acceptance Criteria:**  
  - AI Market Scan bisa benar-benar membuka posisi di akun test (dengan pengaturan yang aman).

---

### Phase 7 – AI Position Management (Optional v1.1)

#### Task 7.1 – AiPositionManagerService
- **Priority:** MEDIUM  
- **Estimate:** 2–3 hari  
- **Description:**  
- Implement service untuk:
  - Ambil open positions + market data.
  - Panggil AI (mode POSITION_MGMT) + baca preset (BE/TS rule).
  - Hasilkan action: adjust SL, partial close, dsb.
- **Acceptance Criteria:**  
  - Unit tests dengan posisi dummy.

#### Task 7.2 – Cron Job Position Management + Integrasi Adapter
- **Priority:** MEDIUM  
- **Estimate:** 2 hari  
- **Description:**  
  - Job yang jalan periodik:
    - Panggil `AiPositionManagerService`.
    - Eksekusi action ke broker/exchange via adapter.
- **Acceptance Criteria:**  
  - Minimal 1 skenario (mis. move SL ke BE) bekerja di akun test.

---

### Phase 8 – AI Usage Tracking & Plan Integration

#### Task 8.1 – AI Usage Counters Schema & Service
- **Priority:** MEDIUM  
- **Estimate:** 1–1.5 hari  
- **Description:**  
  - Tabel `ai_usage_stats` (atau integrasi ke tabel existing):
    - user_id, mode, calls_used, calls_limit, period_start, period_end.
  - Service untuk:
    - Increment pada setiap call AI.
    - Cek limit sebelum call.
- **Acceptance Criteria:**  
  - Counter naik tiap pemanggilan AI (confirm/scan/position).

#### Task 8.2 – Integrasi ke Plan / Subscription
- **Priority:** MEDIUM  
- **Estimate:** 1–2 hari  
- **Description:**  
  - Tambah mapping per plan:
    - Plan Basic/Pro/Elite → limit AI call per mode.
  - Saat user punya plan tertentu:
    - Apply limit ke `ai_usage_stats`.
- **Acceptance Criteria:**  
  - User yang melewati limit:
    - AI dimatikan otomatik (fallback ke preset/filter non-AI).
    - Notifikasi jelas dikirim.

---

### Phase 9 – Polish, Observability & Safety

#### Task 9.1 – Logging & Audit Trails
- **Priority:** HIGH  
- **Estimate:** 1–1.5 hari  
- **Description:**  
  - Tambah logging terstruktur untuk:
    - Filter result.
    - AI result + decision.
    - Final eksekusi (execute/skip/size_down).
- **Acceptance Criteria:**  
  - Bisa trace satu trade dari:
    - Source → Filter → AI → Decision → Order di log.

#### Task 9.2 – UX Surface Minimal (Status & Debug)
- **Priority:** MEDIUM  
- **Estimate:** 1–2 hari  
- **Description:**  
  - Di admin panel:
    - Tampilkan di detail Signal / Execution:
      - Status filter & AI (pass/fail, skor, reason).
- **Acceptance Criteria:**  
  - Admin bisa cepat lihat kenapa signal dieksekusi/direject.

---

### MVP Recommendation

Untuk rilis pertama yang realistis dan aman:

1. Selesaikan **Phase 1–3 + 4.1–4.3 + 5.1–5.4**:
   - Artinya:
     - Filter Strategy basic.
     - AI Confirm untuk Telegram.
     - Integrasi ke Trading Preset + Execution Engine.
2. Jadikan **Market Scan** (Phase 6) & **Position Management AI** (Phase 7) sebagai tahap berikutnya setelah Telegram+AI Confirm stabil. 


