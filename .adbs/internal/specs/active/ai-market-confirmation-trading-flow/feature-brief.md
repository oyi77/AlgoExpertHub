## Feature: AI Market Confirmation Trading Flow

**Tanggal:** 2025-12-02  
**Mode:** `/brief` (quick feature brief)  
**Status:** Draft

### 1. Ringkasan Singkat Sistem Saat Ini

- **Core System:** Signal Management System.
  - Kumpulkan signal dari berbagai channel (`ChannelSource` → `ChannelMessage` → parsing → `Signal`).
  - Distribusi signal ke user (plans, subscriptions, notifikasi).
- **Multi-Channel Signal Addon:**
  - Menerima pesan dari Telegram/API/RSS/Web scraping.
  - Parsing lewat `ParsingPipeline` (regex + `AiMessageParser`).
  - Hasil parsing → `AutoSignalService` → buat `Signal` (draft/auto_created).
- **Trading Execution Engine Addon:**
  - Listen event signal publish → `SignalPublishedListener`.
  - Eksekusi ke broker/exchange via `SignalExecutionService` + `ExecutionConnection` + adapters.
- **Trading Preset Addon:**
  - Mengatur preset / template eksekusi (risk, lot, dll) per user/connection/signal (detail di spec trading-preset).

### 2. Jawaban Pertanyaan User (Context)

1. **Apakah sistem ini mengeksekusi hanya signal saja?**  
   - Ya, eksekusi trading di engine sekarang **selalu berbasis `Signal`**. Sumbernya bisa manual admin atau auto dari channel, tapi yang dieksekusi tetap objek `Signal` yang sudah dipublish.

2. **Apakah AI hanya digunakan untuk parsing signal saja?**  
   - Ya, AI yang ada sekarang (`AiMessageParser`) dipakai untuk **membaca teks pesan** (mis. Telegram) dan mengekstrak struktur signal (pair, direction, entry, SL, TP, timeframe, dll).  
   - Belum ada AI yang **ambil data market (candles/orderbook) lalu analisa market secara mandiri** untuk bikin keputusan entry.

3. **Apakah belum ada fitur ambil data market → analisa dengan AI → ambil posisi?**  
   - Saat ini **belum ada** flow resmi seperti itu.  
   - Sistem sudah punya:
     - Execution connections + adapters (bisa call API broker/exchange, dapat harga/balance).
     - Flag `needs_price_fetch` di `ParsedSignalData` (untuk kasus harga market entry perlu diambil).  
   - Tapi **belum ada**:
     - Service terpusat untuk tarik data market berkala (OHLCV, indikator, dsb).
     - AI model khusus untuk market analysis yang menghasilkan rekomendasi trade tanpa harus ada pesan signal dari luar.

### 3. Tujuan Feature Baru

Membuat **flow trading yang memadukan dua sumber keputusan**:

1. **Forward Telegram Signal (Existing + AI Parsing)**  
   - Pesan Telegram → AI parsing → hasilnya dibandingkan dengan analisa market AI internal.  
   - Hanya jika hasilnya “sejalan / aman”, maka posisi dieksekusi lewat trading preset.

2. **AI Market Analysis (New)**  
   - Secara berkala sistem tarik data market (per pair/timeframe yang di-config).  
   - AI menganalisa market dan menghasilkan rekomendasi trade (buy/sell + entry/SL/TP).  
   - Jika memenuhi rule keamanan/konfirmasi, sistem buat `Signal` dan eksekusi via trading preset.

Semua entry tetap **diatur via Trading Preset**, supaya konsisten dengan eksekusi yang sudah ada.

### 4. User Story Utama

- **Sebagai owner / admin**, saya ingin:
  - Bisa meneruskan signal dari Telegram (provider) ke akun trading saya, **tapi tetap difilter** oleh AI analisa market internal agar mengurangi signal jelek.
  - Bisa mengaktifkan bot yang **otomatis scan market** dan entry berdasarkan analisa AI, menggunakan rule risk management yang diatur di trading preset.

- **Sebagai user akhir (trader)**, saya ingin:
  - Menghubungkan akun exchange/broker saya.
  - Memilih preset (lot/risk/strategi) yang dipakai ketika:
    - Signal Telegram yang lolos konfirmasi AI dieksekusi.
    - Rekomendasi entry dari AI market analysis dieksekusi.

### 5. High-Level Flow yang Diinginkan

#### 5.1. Flow A – Telegram → AI Parsing → AI Market Confirmation → Eksekusi

1. **Channel Message Masuk**
   - Sumber: Telegram channel / bot / API lain → `ChannelMessage` (status `pending`) di Multi-Channel Signal Addon.

2. **AI Parsing Signal (Existing)**
   - `ProcessChannelMessage` → `ParsingPipeline` (regex + `AiMessageParser`).
   - Output: `ParsedSignalData` (pair, direction, entry/SL/TP, timeframe, confidence, `needs_price_fetch`, dst).

3. **Normalisasi Menjadi Draft Signal**
   - `AutoSignalService` membuat `Signal` draft (atau auto-publish tergantung config channel).

4. **Market Context Fetch (New)**
   - Service baru, misalnya `MarketContextService`, dipanggil dengan:
     - Symbol / pair (`Signal->pair`).
     - Timeframe dari signal (atau default).
   - Mengambil data:
     - OHLCV terakhir N candles dari broker/exchange (via adapter yang sama dengan execution engine, atau dedicated market data connector).
     - Optional: indikator teknikal (MA, RSI, dll) dihitung lokal.

5. **AI Market Analysis (New)**
   - Service baru, misalnya `MarketAnalysisAiService`, menggunakan:
     - Data market (candles + indikator).
     - Data signal (direction, entry, SL, TP, timeframe).
   - Output contoh:
     - `alignment` (e.g. `aligned`, `weakly_aligned`, `against_trend`).
     - `risk_score` / `safety_score`.
     - `recommendation` (`accept`, `reject`, `size_down`, `manual_review`).

6. **Keputusan Eksekusi Berdasarkan Trading Preset**
   - Rule per preset, misalnya:
     - Hanya eksekusi jika `alignment = aligned` dan `safety_score >= threshold`.
     - Jika `size_down` → pakai lot lebih kecil dari preset.
     - Jika `reject` → tandai signal sebagai “ditolak oleh AI market” (log/audit).

7. **Eksekusi via Trading Execution Engine**
   - Jika lolos rule:
     - Pastikan `Signal` dipublish (atau buat “synthetic publish event” khusus execution).
     - Jalankan `SignalExecutionService` dengan trading preset terpilih (lot sizing / risk).

#### 5.2. Flow B – Periodic Market Scan → AI Market Analysis → Buat Signal → Eksekusi

1. **Scheduler / Cron Job (New)**
   - Job baru misalnya `RunMarketScanJob` di addon (atau di core dengan integrasi ke addon).
   - Berjalan per interval (contoh: setiap 5 menit / 15 menit / 1 jam).
   - Loop pair & timeframe yang sudah di-config di addon.

2. **Market Data Fetch**
   - Untuk setiap pair/timeframe:
     - Ambil data candles terakhir (OHLCV) dari exchange/broker.
     - Hitung indikator jika diperlukan.

3. **AI Market-Only Analysis**
   - Panggil `MarketAnalysisAiService` dengan input hanya dari market data.
   - Output bisa berupa:
     - `should_open_trade` (boolean).
     - `direction` (buy/sell).
     - `entry_type` (market/limit).
     - `entry_price` (jika limit).
     - `sl` / `tp` / multiple TP (jika didukung).
     - Confidence / safety score.

4. **Generate Internal Signal**
   - Jika `should_open_trade = true` dan confidence >= threshold:
     - Buat `Signal` baru dengan `auto_created = 1`, `channel_source_id` khusus “AI Market Bot”.
     - Attach ke plan / channel assignment yang sesuai (misalnya plan khusus “AI Bot”).

5. **Eksekusi via Preset**
   - Publish signal (atau trigger langsung ke execution engine).
   - Gunakan trading preset:
     - Menentukan lot/risk.
     - Menentukan exchange/broker connection mana yang dipakai.

6. **Optional: AI Post-Execution Monitoring (Future)**
   - Di luar scope brief, tetapi ke depan bisa:
     - AI memonitor posisi, adjust SL/TP, atau close awal.

### 6. Requirement Utama (Ringkas)

- **R1 – Integrasi Market Data:**
  - Service untuk ambil OHLCV + indikator per pair/timeframe, menggunakan adapter existing atau konektor baru.

- **R2 – Market Analysis AI:**
  - Service AI baru (bisa reuse AiProviderFactory dengan prompt berbeda) untuk:
    - Mode 1: “Signal + Market Context Validation”.
    - Mode 2: “Pure Market Scan (tanpa signal) → rekomendasi trade”.

- **R3 – Decision Engine + Rule Layer:**
  - Lapisan rule yang:
    - Menerima rekomendasi AI.
    - Menggabungkan dengan konfigurasi Trading Preset.
    - Menghasilkan keputusan final: execute / skip / size down / manual review.

- **R4 – Integrasi dengan Trading Preset:**
  - Mapping dari:
    - Channel Source / AI Market Bot → Trading Preset default.
    - User-specific override jika user punya beberapa preset.

- **R5 – Observability & Safety:**
  - Logging jelas:
    - Input pesan / data market.
    - Output AI (analisa + recommendation).
    - Keputusan final (execute/skip).
  - Config global untuk:
    - Enable/disable flow A & B.
    - Limit max trades per periode.

### 7. Catatan Teknis & Next Steps Cepat

- **Addon & Namespace:**
  - Implementasi sebaiknya tetap di dalam addon:
    - Komponen yang terkait channel & parsing: `Addons\MultiChannelSignalAddon`.
    - Komponen analisa market dan eksekusi: bisa di addon baru atau integrasi dengan `TradingExecutionEngine` + `TradingPreset`.

- **Reuse yang Ada:**
  - Gunakan:
    - `AiMessageParser` + `AiProviderFactory` sebagai contoh integrasi AI.
    - `SignalExecutionService` untuk eksekusi final.
    - `ExecutionConnection` untuk koneksi broker/exchange.

- **Tahap Implementasi Cepat (High Level):**
  1. Tambah service `MarketDataService` (ambil OHLCV via adapters).
  2. Tambah `MarketAnalysisAiService` (2 mode: confirm signal, pure market).
  3. Tambah decision layer + config (threshold, alignment rule) yang bisa baca Trading Preset.
  4. Buat scheduler job untuk periodic market scan.
  5. Hook ke flow existing:
     - Setelah `AutoSignalService` buat signal dari Telegram → panggil market confirmation sebelum eksekusi.


