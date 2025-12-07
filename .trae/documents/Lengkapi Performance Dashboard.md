## Tujuan
- Menambah fitur optimasi ala plugin WordPress (WP Rocket/W3TC) ke Performance Dashboard admin tanpa mengganggu arsitektur yang ada.
- Memanfaatkan konfigurasi, middleware, dan service terpusat agar fleksibel, aman, dan mudah dipelihara.

## Fitur Baru yang Ditambahkan
- Frontend Optimization: lazy-load gambar, defer/async `<script>`, preload/prefetch aset, inline critical CSS (opsional), pengaturan `ASSET_URL` untuk CDN.
- Media Optimization: kompresi & konversi WebP saat upload (via `intervention/image`), batas dimensi maksimum, pembersihan thumbnail yatim.
- HTTP Caching: header `Cache-Control`/`ETag` terkelola, whitelist/blacklist path, opsi no-cache untuk halaman sensitif.
- Cache & Prewarm: tombol prewarm untuk merender dan meng-cache halaman umum; TTL per modul; cache tag-aware untuk query berat (contoh Dashboard user).
- Database Cleanup: prune `jobs/failed_jobs`, log lama, optimize tabel (MySQL `OPTIMIZE TABLE`), vacuum (SQLite), menjalankan indeks migrasi yang relevan.
- Scheduler & Health: jadwal rutin cleanup/prewarm; ringkasan metrik (memori, OPcache, queue) tetap via SSE yang sudah ada.

## Integrasi dengan Sistem Saat Ini
- UI Performance sudah ada di `resources/views/backend/setting/performance.blade.php` (main/resources/views/backend/setting/performance.blade.php). Kita akan menambah seksi dan tombol aksi baru di file ini dan panel addon `algoexpert-plus`.
- Aksi backend saat ini di `ConfigurationController` (main/app/Http/Controllers/Backend/ConfigurationController.php:913, 1062). Kita perlu menambah endpoint aksi baru dan memanfaatkan SSE/status yang sudah tersedia (main/app/Http/Controllers/Backend/ConfigurationController.php:56, 83).
- Registrasi addon sudah siap via `AppServiceProvider` (main/app/Providers/AppServiceProvider.php:59–77) dan `AddonServiceProvider` (main/addons/algoexpert-plus-addon/AddonServiceProvider.php:52–65); rute admin tersedia.

## Desain Teknis
- Config pusat: simpan preferensi di tabel `global_configurations` dengan key `performance` (tersedia via model `GlobalConfiguration`, main/app/Models/GlobalConfiguration.php). Struktur contoh: `{ frontend: { lazy_images, defer_js, preload }, media: { compress, webp }, http: { cache_headers, etag, whitelist }, cache: { ttl_map, prewarm }, db: { prune_days, optimize_tables } }`.
- Middleware `OptimizeFrontendMiddleware`: jika response `text/html`, injeksi `loading="lazy"` pada `<img>`, set `defer`/`async` pada `<script>` sesuai whitelist/blacklist, tambahkan resource hints (preload/dns-prefetch) dari config.
- Service `PerformanceOptimizationService`:
  - `applyHttpCaching(Response)`: set header cache/etag sesuai config.
  - `prewarmRoutes()` dan `prewarmViews()`: kunjungi route umum (home, dashboard, katalog) dan memicu cache view/route.
  - `cleanupDatabase()`: prune job/log lama, panggil `OPTIMIZE TABLE` per driver; aman-idempotent.
  - `optimizeMedia(UploadedFile)`: kompresi, konversi WebP jika didukung (gd/imagick), simpan berdampingan.
- Hook upload: gunakan service pada jalur upload yang sudah ada (ConfigurationService dan pengelola media) tanpa mengubah API eksternal.
- Caching query: contoh di `UserDashboardService::dashboard()` (main/app/Services/UserDashboardService.php:16) akan dibungkus `Cache::remember` (TTL dikendalikan dari config, tag `user:{id}`) untuk grafik/aggregasi.

## Perubahan File & Komponen
- Tambah `config/performance.php` (default & dokumentasi opsi).
- Tambah `app/Http/Middleware/OptimizeFrontendMiddleware.php` dan daftarkan di kernel untuk grup `web` dengan toggle enable.
- Tambah `app/Services/PerformanceOptimizationService.php`.
- Perluas `ConfigurationController` dengan aksi baru:
  - `performance/assets`, `performance/http`, `performance/media`, `performance/cache`, `performance/db` untuk POST tombol aksi.
  - `performance/prewarm` (jalankan prewarm & laporkan hasil).
- Perbarui `routes/admin.php` (main/routes/admin.php:159–175 sebagai referensi) untuk rute aksi baru di prefix `general`.
- Perluas Blade `backend/setting/performance.blade.php` dengan seksi:
  - Frontend Optimization (toggle & tombol uji cepat)
  - Media Optimization (toggle kompresi/WebP)
  - HTTP Caching (preset header, whitelist/blacklist)
  - Cache & Prewarm (TTL per modul, tombol prewarm)
  - Database Cleanup (parameter prune, optimize tables)
- Optional: Blade partial di addon `algoexpert-plus` untuk menampilkan seksi baru di halaman `system-tools/performance` (main/addons/algoexpert-plus-addon/resources/views/backend/system-tools/performance.blade.php:17).

## Verifikasi
- Manual: jalankan tombol optimasi dan lihat notifikasi sukses/error via AJAX (sudah ada di view), cek header response pada halaman publik, cek ukuran media tersimpan, cek jumlah jobs/log setelah prune.
- Unit: uji service method (mock driver DB, cek set header, simulasi uploaded file), uji middleware pada HTML sederhana.
- Observasi: gunakan SSE yang sudah ada untuk memantau dampak (memori, OPcache, queue).

## Keamanan & Risiko
- Middleware hanya memodifikasi HTML non-API; daftar pengecualian route disediakan.
- Operasi DB cleanup bersifat opt-in dengan konfirmasi, batas prune default aman.
- Prewarm menghormati rate-limit dan tidak memproses halaman admin.
- Media konversi menyimpan file asli; fallback jika `imagick/gd` tidak tersedia.

## Output yang Diharapkan
- Dashboard Performance dengan fitur optimasi komprehensif, tombol aksi bekerja via AJAX, konfigurasi tersimpan di `global_configurations`, dampak terlihat di metrik SSE dan waktu muat halaman yang lebih cepat.