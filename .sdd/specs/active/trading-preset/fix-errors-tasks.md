# Fix Trading Preset UI Errors - Task Breakdown

## Overview

Fix error 500 pada semua page dan masalah UI preset yang tidak muncul. Error kemungkinan disebabkan oleh:
1. JavaScript files di-include sebagai blade views (harusnya script tag)
2. Variable `$preset` null di create view
3. Missing route definitions
4. View namespace issues

## Task List

### Task 1: Fix JavaScript Include Issues
**Status:** TODO  
**Priority:** CRITICAL  
**Estimate:** 30 minutes

**Problem:**
JavaScript files (`.js`) di-include menggunakan `@include()` yang akan mencoba parse sebagai Blade template, menyebabkan error.

**Solution:**
- Ubah `@include('trading-preset-addon::backend.presets.js.conditional-fields')` menjadi `<script>` tag dengan `@push('script')`
- Ubah `@include('trading-preset-addon::backend.presets.js.validation')` menjadi `<script>` tag dengan `@push('script')`
- Atau rename file `.js` menjadi `.blade.php` jika ingin tetap menggunakan include

**Files to Fix:**
- `addons/trading-preset-addon/resources/views/backend/presets/create.blade.php`
- `addons/trading-preset-addon/resources/views/backend/presets/edit.blade.php`
- `addons/trading-preset-addon/resources/views/user/presets/create.blade.php`
- `addons/trading-preset-addon/resources/views/user/presets/edit.blade.php`

**Acceptance Criteria:**
- [ ] JavaScript files tidak di-include sebagai blade views
- [ ] Scripts tetap berfungsi dengan baik
- [ ] Tidak ada error saat load page

---

### Task 2: Fix Null Preset Variable in Partials
**Status:** TODO  
**Priority:** HIGH  
**Estimate:** 20 minutes

**Problem:**
Di create view, `$preset` adalah null, tapi partials menggunakan `$preset->field` yang akan error.

**Solution:**
- Update semua partials untuk handle null `$preset`
- Gunakan null coalescing operator (`??`) atau check `isset($preset)`
- Atau set `$preset = null` di create controller dan handle di views

**Files to Fix:**
- `addons/trading-preset-addon/resources/views/backend/presets/partials/basic-info.blade.php`
- `addons/trading-preset-addon/resources/views/backend/presets/partials/position-risk.blade.php`
- `addons/trading-preset-addon/resources/views/backend/presets/partials/sl-tp.blade.php`
- `addons/trading-preset-addon/resources/views/backend/presets/partials/advanced-features.blade.php`
- `addons/trading-preset-addon/resources/views/backend/presets/partials/layering-hedging.blade.php`
- `addons/trading-preset-addon/resources/views/backend/presets/partials/schedule-target.blade.php`

**Acceptance Criteria:**
- [ ] Semua partials handle null `$preset` dengan benar
- [ ] Create view tidak error saat load
- [ ] Form fields kosong di create view (expected behavior)

---

### Task 3: Verify Route Registration
**Status:** TODO  
**Priority:** HIGH  
**Estimate:** 15 minutes

**Problem:**
Routes mungkin tidak terdaftar dengan benar, menyebabkan 404 atau error.

**Solution:**
- Verify routes file exists
- Check route registration di AddonServiceProvider
- Test route dengan `php artisan route:list`
- Ensure route names match dengan yang digunakan di views

**Files to Check:**
- `addons/trading-preset-addon/routes/admin.php`
- `addons/trading-preset-addon/routes/web.php`
- `addons/trading-preset-addon/AddonServiceProvider.php`

**Acceptance Criteria:**
- [ ] Routes terdaftar dengan benar
- [ ] Route names match dengan views
- [ ] Routes accessible tanpa error

---

### Task 4: Fix View Namespace Issues
**Status:** TODO  
**Priority:** MEDIUM  
**Estimate:** 15 minutes

**Problem:**
View namespace mungkin tidak ter-load dengan benar.

**Solution:**
- Verify `loadViewsFrom()` di AddonServiceProvider
- Check namespace `trading-preset-addon` digunakan dengan benar
- Ensure view files ada di lokasi yang benar

**Files to Check:**
- `addons/trading-preset-addon/AddonServiceProvider.php`
- All view files using `trading-preset-addon::` namespace

**Acceptance Criteria:**
- [ ] Views ter-load dengan benar
- [ ] Namespace consistent di semua views
- [ ] Tidak ada "View not found" errors

---

### Task 5: Fix Menu Item Conditional Logic
**Status:** TODO  
**Priority:** MEDIUM  
**Estimate:** 10 minutes

**Problem:**
Menu items mungkin tidak muncul karena conditional logic salah atau addon tidak terdeteksi aktif.

**Solution:**
- Verify addon status di database
- Check `AddonRegistry::active()` dan `moduleEnabled()` calls
- Ensure menu items hanya muncul jika addon dan module aktif

**Files to Check:**
- `resources/views/backend/layout/sidebar.blade.php`
- `resources/views/frontend/*/layout/user_sidebar.blade.php`

**Acceptance Criteria:**
- [ ] Menu items muncul jika addon aktif
- [ ] Menu items tidak muncul jika addon tidak aktif
- [ ] Tidak ada error saat check addon status

---

### Task 6: Clear Cache and Test
**Status:** TODO  
**Priority:** HIGH  
**Estimate:** 10 minutes

**Problem:**
Laravel cache mungkin menyimpan error atau view yang lama.

**Solution:**
- Clear view cache: `php artisan view:clear`
- Clear config cache: `php artisan config:clear`
- Clear route cache: `php artisan route:clear`
- Clear application cache: `php artisan cache:clear`
- Test semua pages

**Commands:**
```bash
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

**Acceptance Criteria:**
- [ ] Semua cache cleared
- [ ] Pages load tanpa error 500
- [ ] UI preset muncul dengan benar

---

## Summary

**Total Tasks:** 6  
**Total Estimated Time:** 1.5 hours  
**Priority Breakdown:**
- CRITICAL: 1 task (30 min)
- HIGH: 3 tasks (55 min)
- MEDIUM: 2 tasks (25 min)

**Recommended Order:**
1. Task 1 (JavaScript includes) - CRITICAL
2. Task 2 (Null preset variable) - HIGH
3. Task 6 (Clear cache) - HIGH
4. Task 3 (Route verification) - HIGH
5. Task 4 (View namespace) - MEDIUM
6. Task 5 (Menu items) - MEDIUM

**Notes:**
- Task 1 dan 2 kemungkinan besar adalah root cause dari error 500
- Setelah fix Task 1 dan 2, clear cache (Task 6) untuk memastikan changes ter-apply
- Test setiap task sebelum lanjut ke task berikutnya

