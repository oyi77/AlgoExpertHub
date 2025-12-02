# Trading Preset UI Views - Task Breakdown

## Overview

Create comprehensive UI views for Trading Preset addon including admin and user interfaces with complex form configurations for all preset features.

## Task List

### Phase 1: Admin Views Foundation

#### Task 1.1: Admin Index View
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Create admin index view for listing all trading presets with search, filtering, and statistics.

**Acceptance Criteria:**
- [ ] View displays preset list in table format
- [ ] Search functionality (name, description, symbol)
- [ ] Filters: visibility, default template, enabled status
- [ ] Statistics cards: total, default, public, private, enabled, disabled
- [ ] Action buttons: create, edit, clone, toggle status, delete
- [ ] Pagination working
- [ ] Responsive design
- [ ] Follows existing admin UI patterns

**Files to Create:**
- `addons/trading-preset-addon/resources/views/backend/presets/index.blade.php`

**Dependencies:**
- None

**Technical Notes:**
- Use `@extends('backend.layout.master')`
- Follow pattern from `resources/views/backend/admins/index.blade.php`
- Display: name, description, symbol, visibility, enabled status, creator, usage count
- Include stats from controller

---

#### Task 1.2: Admin Create/Edit Form - Basic Information
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create form section for basic preset information (Identity & Market section).

**Acceptance Criteria:**
- [ ] Name field (required)
- [ ] Description textarea
- [ ] Symbol field (optional, with autocomplete suggestions)
- [ ] Timeframe dropdown (M1, M5, M15, H1, H4, D1, etc.)
- [ ] Enabled toggle switch
- [ ] Tags input (multi-select or comma-separated)
- [ ] Form validation messages
- [ ] Responsive layout

**Files to Create:**
- Partial: `addons/trading-preset-addon/resources/views/backend/presets/partials/basic-info.blade.php`

**Dependencies:**
- Task 1.1

**Technical Notes:**
- Use Bootstrap form components
- Tags can use select2 or similar
- Timeframe dropdown with common options

---

#### Task 1.3: Admin Create/Edit Form - Position & Risk
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Create form section for position sizing and risk management.

**Acceptance Criteria:**
- [ ] Position Size Mode radio/select (FIXED, RISK_PERCENT)
- [ ] Conditional fields:
  - If FIXED: show fixed_lot input
  - If RISK_PERCENT: show risk_per_trade_pct input
- [ ] Max Positions input
- [ ] Max Positions Per Symbol input
- [ ] Dynamic Equity Mode select (NONE, LINEAR, STEP)
- [ ] Conditional fields for dynamic equity:
  - If LINEAR/STEP: show equity_base, equity_step_factor
  - Show risk_min_pct, risk_max_pct
- [ ] JavaScript to show/hide conditional fields
- [ ] Validation feedback

**Files to Create:**
- Partial: `addons/trading-preset-addon/resources/views/backend/presets/partials/position-risk.blade.php`

**Dependencies:**
- Task 1.2

**Technical Notes:**
- Use JavaScript for conditional field visibility
- Add tooltips/help text for complex fields
- Show risk warnings for high percentages (>10%)

---

#### Task 1.4: Admin Create/Edit Form - Stop Loss & Take Profit
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 5 hours  
**Priority:** HIGH

**Description:**
Create form section for SL and TP configuration with multi-TP support.

**Acceptance Criteria:**
- [ ] SL Mode select (PIPS, R_MULTIPLE, STRUCTURE)
- [ ] Conditional SL fields based on mode
- [ ] TP Mode select (DISABLED, SINGLE, MULTI)
- [ ] Single TP: TP1 fields (enabled, R:R, close %)
- [ ] Multi-TP: TP1, TP2, TP3 fields
- [ ] Each TP: enabled toggle, R:R input, close % input
- [ ] "Close remaining at TP3" checkbox for multi-TP
- [ ] Visual indicators for R:R ratios
- [ ] JavaScript for mode switching

**Files to Create:**
- Partial: `addons/trading-preset-addon/resources/views/backend/presets/partials/sl-tp.blade.php`

**Dependencies:**
- Task 1.3

**Technical Notes:**
- Use accordion or tabs for better organization
- Show/hide TP2/TP3 fields based on TP mode
- Add visual preview of TP levels

---

#### Task 1.5: Admin Create/Edit Form - Advanced Features (Break-Even & Trailing Stop)
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** MEDIUM

**Description:**
Create form section for break-even and trailing stop features.

**Acceptance Criteria:**
- [ ] Break-Even section:
  - Enabled toggle
  - Trigger R:R input
  - Offset pips input
- [ ] Trailing Stop section:
  - Enabled toggle
  - Trigger R:R input
  - Mode select (STEP_PIPS, STEP_ATR, CHANDELIER)
  - Conditional fields based on mode:
    - STEP_PIPS: step_pips input
    - STEP_ATR: atr_period, atr_multiplier inputs
    - CHANDELIER: atr_period, atr_multiplier inputs
  - Update interval (seconds) input
- [ ] Help text explaining each mode
- [ ] JavaScript for conditional fields

**Files to Create:**
- Partial: `addons/trading-preset-addon/resources/views/backend/presets/partials/advanced-features.blade.php`

**Dependencies:**
- Task 1.4

**Technical Notes:**
- Use collapsible sections for better UX
- Add tooltips for ATR and Chandelier modes

---

#### Task 1.6: Admin Create/Edit Form - Layering, Hedging & Exit Logic
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** MEDIUM

**Description:**
Create form section for layering/grid, hedging, and candle-based exit.

**Acceptance Criteria:**
- [ ] Layering/Grid section:
  - Enabled toggle
  - Max layers per symbol input
  - Layer distance (pips) input
  - Martingale mode select (NONE, MULTIPLY, ADD)
  - Martingale factor input (conditional)
  - Max total risk % input
- [ ] Hedging section:
  - Enabled toggle
  - Trigger drawdown % input
  - Hedge distance (pips) input
  - Lot factor input
- [ ] Exit Per Candle section:
  - Auto close on candle close toggle
  - Timeframe select (conditional)
  - Hold max candles input (conditional)
- [ ] JavaScript for conditional fields

**Files to Create:**
- Partial: `addons/trading-preset-addon/resources/views/backend/presets/partials/layering-hedging.blade.php`

**Dependencies:**
- Task 1.5

**Technical Notes:**
- Group related features in collapsible sections
- Add warnings for high-risk configurations

---

#### Task 1.7: Admin Create/Edit Form - Trading Schedule & Weekly Target
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** MEDIUM

**Description:**
Create form section for trading schedule and weekly target settings.

**Acceptance Criteria:**
- [ ] Trading Schedule section:
  - "Only trade in session" toggle
  - Trading hours start/end time inputs
  - Timezone select
  - Trading days checkboxes (Mon-Sun)
  - Session profile select (ASIA, LONDON, NY, CUSTOM)
  - Conditional fields based on session profile
- [ ] Weekly Target section:
  - Enabled toggle
  - Weekly target profit % input
  - Weekly reset day select (Mon-Sun)
  - Auto stop on weekly target toggle
- [ ] JavaScript for day mask calculation
- [ ] Visual calendar/day selector

**Files to Create:**
- Partial: `addons/trading-preset-addon/resources/views/backend/presets/partials/schedule-target.blade.php`

**Dependencies:**
- Task 1.6

**Technical Notes:**
- Use time picker for hours
- Convert day checkboxes to bitmask for backend
- Show session time ranges for each profile

---

#### Task 1.8: Admin Create View
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create admin create view that combines all form partials.

**Acceptance Criteria:**
- [ ] Uses all form partials (Tasks 1.2-1.7)
- [ ] Form organized in tabs or accordion
- [ ] Form validation (client-side and server-side)
- [ ] Submit button with loading state
- [ ] Success/error messages
- [ ] Cancel button
- [ ] Responsive design
- [ ] Follows admin layout pattern

**Files to Create:**
- `addons/trading-preset-addon/resources/views/backend/presets/create.blade.php`

**Dependencies:**
- Tasks 1.2-1.7

**Technical Notes:**
- Use tabs: Basic, Position & Risk, SL/TP, Advanced, Schedule
- Include form validation JavaScript
- Use Laravel form helpers

---

#### Task 1.9: Admin Edit View
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 2 hours  
**Priority:** HIGH

**Description:**
Create admin edit view (reuses create form with preset data).

**Acceptance Criteria:**
- [ ] Reuses create form structure
- [ ] Pre-fills all fields with preset data
- [ ] Shows preset ID and creation info
- [ ] Update button instead of create
- [ ] Delete button (with confirmation)
- [ ] Clone button
- [ ] Toggle status button

**Files to Create:**
- `addons/trading-preset-addon/resources/views/backend/presets/edit.blade.php`

**Dependencies:**
- Task 1.8

**Technical Notes:**
- Can extend or include create form
- Handle old() values for validation errors
- Show readonly fields for default templates

---

#### Task 1.10: Admin Show View
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Create admin show/view page for preset details.

**Acceptance Criteria:**
- [ ] Display all preset information in readable format
- [ ] Grouped by sections (same as form)
- [ ] Show usage statistics (connections, subscriptions, bots)
- [ ] Action buttons: edit, clone, toggle status, delete
- [ ] Visual indicators for enabled/disabled, visibility
- [ ] Show creator and creation date
- [ ] Responsive design

**Files to Create:**
- `addons/trading-preset-addon/resources/views/backend/presets/show.blade.php`

**Dependencies:**
- Task 1.9

**Technical Notes:**
- Use cards or sections for grouping
- Show badges for status, visibility, etc.
- Include usage count from controller

---

### Phase 2: User Views Foundation

#### Task 2.1: User Index View
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** HIGH

**Description:**
Create user index view for listing user's own presets.

**Acceptance Criteria:**
- [ ] Display user's presets in card or table format
- [ ] Search functionality
- [ ] Filter by enabled/disabled
- [ ] Statistics: total, enabled, disabled
- [ ] Action buttons: create, edit, clone, set as default, toggle status, delete
- [ ] Link to marketplace
- [ ] Responsive design
- [ ] Follows user UI patterns

**Files to Create:**
- `addons/trading-preset-addon/resources/views/user/presets/index.blade.php`

**Dependencies:**
- Task 1.1 (for reference)

**Technical Notes:**
- Use user layout instead of admin layout
- Show which preset is set as default
- Simpler than admin view (no visibility filters)

---

#### Task 2.2: User Marketplace View
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Create marketplace view for browsing public and default presets.

**Acceptance Criteria:**
- [ ] Display public and default presets
- [ ] Card-based layout (better for browsing)
- [ ] Search and filter functionality
- [ ] Show preset preview (name, description, key settings)
- [ ] Clone button for each preset
- [ ] View details button
- [ ] Tags display
- [ ] Sort options (popular, newest, name)
- [ ] Responsive grid layout

**Files to Create:**
- `addons/trading-preset-addon/resources/views/user/presets/marketplace.blade.php`

**Dependencies:**
- Task 2.1

**Technical Notes:**
- Use card layout similar to product listings
- Show key metrics (risk %, SL mode, TP mode)
- Highlight default/system presets

---

#### Task 2.3: User Create View
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 2 hours  
**Priority:** HIGH

**Description:**
Create user create view (simpler version of admin create).

**Acceptance Criteria:**
- [ ] Reuses form partials from admin (Tasks 1.2-1.7)
- [ ] No admin-only fields (is_default_template)
- [ ] Visibility options: PRIVATE, PUBLIC_MARKETPLACE
- [ ] Clonable toggle
- [ ] Form validation
- [ ] Responsive design
- [ ] Follows user layout

**Files to Create:**
- `addons/trading-preset-addon/resources/views/user/presets/create.blade.php`

**Dependencies:**
- Tasks 1.2-1.7, Task 1.8

**Technical Notes:**
- Can reuse admin form partials
- Hide admin-only options
- Simpler UI than admin version

---

#### Task 2.4: User Edit View
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 2 hours  
**Priority:** HIGH

**Description:**
Create user edit view for editing own presets.

**Acceptance Criteria:**
- [ ] Reuses create form
- [ ] Pre-fills with preset data
- [ ] Permission check (can only edit own presets)
- [ ] Update, delete, clone buttons
- [ ] Set as default button
- [ ] Toggle status button
- [ ] Responsive design

**Files to Create:**
- `addons/trading-preset-addon/resources/views/user/presets/edit.blade.php`

**Dependencies:**
- Task 2.3

**Technical Notes:**
- Similar to admin edit but with user permissions
- Show warning if preset is in use

---

### Phase 3: Form Components & JavaScript

#### Task 3.1: Form JavaScript - Conditional Fields
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 4 hours  
**Priority:** HIGH

**Description:**
Create JavaScript for showing/hiding conditional form fields.

**Acceptance Criteria:**
- [ ] Position size mode switching (FIXED/RISK_PERCENT)
- [ ] Dynamic equity mode switching
- [ ] SL mode switching (PIPS/R_MULTIPLE/STRUCTURE)
- [ ] TP mode switching (SINGLE/MULTI)
- [ ] Trailing stop mode switching
- [ ] Feature toggles (break-even, trailing stop, layering, etc.)
- [ ] Trading schedule conditional fields
- [ ] Smooth transitions
- [ ] Preserve values when hiding/showing

**Files to Create:**
- `addons/trading-preset-addon/resources/views/backend/presets/js/conditional-fields.js`
- `addons/trading-preset-addon/resources/views/user/presets/js/conditional-fields.js`

**Dependencies:**
- Tasks 1.2-1.7

**Technical Notes:**
- Use jQuery (already in project)
- Store hidden field values in data attributes
- Initialize on page load

---

#### Task 3.2: Form JavaScript - Validation & Warnings
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Create client-side validation and risk warnings.

**Acceptance Criteria:**
- [ ] Real-time validation feedback
- [ ] Risk warnings for high percentages (>10%)
- [ ] Validation for interdependent fields
- [ ] R:R ratio validation (TP R:R should be > SL R:R)
- [ ] Max positions validation
- [ ] Visual indicators (red/yellow/green)
- [ ] Tooltips for complex fields
- [ ] Help text for each section

**Files to Create:**
- `addons/trading-preset-addon/resources/views/backend/presets/js/validation.js`
- `addons/trading-preset-addon/resources/views/user/presets/js/validation.js`

**Dependencies:**
- Task 3.1

**Technical Notes:**
- Use Laravel validation rules as reference
- Show warnings, not just errors
- Use Bootstrap validation classes

---

#### Task 3.3: Form JavaScript - Day Mask Calculator
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 2 hours  
**Priority:** LOW

**Description:**
Create JavaScript to convert day checkboxes to bitmask.

**Acceptance Criteria:**
- [ ] Convert day checkboxes to bitmask value
- [ ] Update hidden input with bitmask
- [ ] Load bitmask to checkboxes on edit
- [ ] Handle all 7 days (Mon-Sun)
- [ ] Visual feedback

**Files to Create:**
- Include in `conditional-fields.js`

**Dependencies:**
- Task 1.7

**Technical Notes:**
- Bitmask: 1=Mon, 2=Tue, 4=Wed, 8=Thu, 16=Fri, 32=Sat, 64=Sun
- Calculate sum of checked days

---

### Phase 4: Integration & Polish

#### Task 4.1: Add Admin Menu Item
**Status:** TODO  
**Assignee:** Backend/Frontend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Add Trading Presets menu item to admin sidebar.

**Acceptance Criteria:**
- [ ] Menu item in admin sidebar
- [ ] Icon (feather icon)
- [ ] Link to preset index
- [ ] Permission check (manage-addon or admin)
- [ ] Active state highlighting

**Files to Modify:**
- `resources/views/backend/layout/sidebar.blade.php`

**Dependencies:**
- Task 1.1

**Technical Notes:**
- Check if addon is active and module enabled
- Use permission: `manage-addon` or `admin`

---

#### Task 4.2: Add User Menu Item
**Status:** TODO  
**Assignee:** Backend/Frontend Developer  
**Estimate:** 1 hour  
**Priority:** HIGH

**Description:**
Add Trading Presets menu item to user navigation.

**Acceptance Criteria:**
- [ ] Menu item in user navigation
- [ ] Icon
- [ ] Link to user preset index
- [ ] Active state highlighting

**Files to Modify:**
- User navigation file (to be identified)

**Dependencies:**
- Task 2.1

**Technical Notes:**
- Check if addon is active and user_ui module enabled

---

#### Task 4.3: Form Styling & Responsive Design
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 3 hours  
**Priority:** MEDIUM

**Description:**
Polish form styling and ensure responsive design.

**Acceptance Criteria:**
- [ ] All forms responsive on mobile
- [ ] Consistent spacing and typography
- [ ] Proper form field grouping
- [ ] Visual hierarchy (sections, subsections)
- [ ] Loading states for buttons
- [ ] Error message styling
- [ ] Success message styling
- [ ] Tooltips and help text styling

**Files to Modify:**
- All view files
- Create: `addons/trading-preset-addon/resources/views/backend/presets/css/preset-forms.css`

**Dependencies:**
- All previous tasks

**Technical Notes:**
- Use Bootstrap utilities
- Follow existing admin/user theme
- Test on mobile devices

---

#### Task 4.4: Error Handling & Messages
**Status:** TODO  
**Assignee:** Frontend Developer  
**Estimate:** 2 hours  
**Priority:** MEDIUM

**Description:**
Implement proper error handling and user feedback.

**Acceptance Criteria:**
- [ ] Display validation errors
- [ ] Success messages after create/update
- [ ] Confirmation dialogs for delete
- [ ] Warning messages for risky configurations
- [ ] Info messages for feature explanations
- [ ] Use Laravel flash messages
- [ ] Use project's alert system (iziToast/toastr/sweetalert)

**Files to Modify:**
- All view files
- Include alert partials

**Dependencies:**
- All previous tasks

**Technical Notes:**
- Use `@include('backend.layout.alert')` or similar
- Follow existing alert patterns

---

## Summary

**Total Tasks:** 20  
**Total Estimated Time:** 60 hours (~7.5 days)  
**Priority Breakdown:**
- HIGH: 12 tasks (48 hours)
- MEDIUM: 6 tasks (18 hours)
- LOW: 1 task (2 hours)

**Dependencies:**
- Phase 1 (Admin Views) can be done in parallel with Phase 2 (User Views) after foundation
- Phase 3 (JavaScript) depends on Phase 1 & 2
- Phase 4 (Integration) depends on all previous phases

**Recommended Approach:**
1. Start with Task 1.1 (Admin Index) to establish pattern
2. Build form partials (Tasks 1.2-1.7) in order
3. Combine into create/edit views (Tasks 1.8-1.9)
4. Repeat for user views (Tasks 2.1-2.4)
5. Add JavaScript enhancements (Tasks 3.1-3.3)
6. Final integration and polish (Tasks 4.1-4.4)

**Notes:**
- Form is complex with many conditional fields - prioritize JavaScript for good UX
- Can reuse form partials between admin and user views
- Follow existing UI patterns for consistency
- Test thoroughly on mobile devices
- Consider using tabs or accordion for better organization of long forms

