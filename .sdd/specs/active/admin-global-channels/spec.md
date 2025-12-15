# Specification: Admin-Managed Global Channels

**Created:** 2025-11-11
**Last Updated:** 2025-01-27
**Status:** ACTIVE

## Overview

Extend the Multi-Channel Signal Addon to allow admins to create and manage channel sources (primarily Telegram MTProto) that can be dynamically assigned to specific users, subscription plans, or globally to all users. Signals from admin channels are automatically distributed to assigned recipients.

## User Stories

### Story 1: Admin Creates Channel Source
**As an** admin
**I want to** create a Telegram MTProto channel source from the admin panel
**So that** I can connect to channels I have access to and distribute signals to users

**Acceptance Criteria:**
- [ ] Admin can access "Admin Channels" menu under Signal tools
- [ ] Admin can create channel with MTProto credentials (API ID, API Hash)
- [ ] Admin can authenticate via phone number and verification code
- [ ] Channel is created with admin ownership flag
- [ ] Session files stored in admin namespace

### Story 2: Admin Assigns Channel to Users/Plans/Global
**As an** admin
**I want to** assign a channel to specific users, plans, or globally
**So that** signals from that channel reach the right recipients

**Acceptance Criteria:**
- [ ] Admin can select assignment type: Users, Plans, or Global
- [ ] For Users: Multi-select user list with search
- [ ] For Plans: Multi-select plan list
- [ ] For Global: Single toggle to enable global distribution
- [ ] Admin can see assignment summary (e.g., "5 users, 2 plans, global enabled")
- [ ] Admin can remove individual assignments

### Story 3: Signal Distribution from Admin Channels
**As a** system
**I want to** distribute signals from admin channels to assigned recipients
**So that** users receive signals based on their assignments

**Acceptance Criteria:**
- [ ] When admin channel generates signal, system identifies all recipients
- [ ] For user assignments: Signal attached to user's default plan or specified plan
- [ ] For plan assignments: Signal attached to those plans
- [ ] For global: Signal attached to all active plans or default plan
- [ ] Duplicate signals prevented (same message hash + recipient)
- [ ] Distribution happens asynchronously via queue

### Story 4: Admin Views Channel Assignments
**As an** admin
**I want to** view which users/plans are assigned to each channel
**So that** I can manage assignments effectively

**Acceptance Criteria:**
- [ ] Channel list shows assignment summary
- [ ] Admin can click to see detailed assignment list
- [ ] Shows user names, plan names, or "Global" indicator
- [ ] Admin can remove assignments from detail view

## Functional Requirements

### FR-1: Database Schema Extensions

**Priority:** HIGH

**Details:**
1. Add to `channel_sources` table:
   - `is_admin_owned` BOOLEAN DEFAULT 0
   - `scope` ENUM('user', 'plan', 'global') NULLABLE
   - `user_id` becomes nullable (NULL for admin-owned channels)

2. Create `channel_source_users` pivot table:
   - `channel_source_id` (FK to channel_sources)
   - `user_id` (FK to users)
   - `created_at`, `updated_at`

3. Create `channel_source_plans` pivot table:
   - `channel_source_id` (FK to channel_sources)
   - `plan_id` (FK to plans)
   - `created_at`, `updated_at`

### FR-2: Admin Channel Management Controller

**Priority:** HIGH

**Details:**
- Controller: `Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\AdminChannelController`
- Routes: `/admin/channels` (index, create, store, edit, update, destroy)
- Actions:
  - `index()` - List all admin-owned channels
  - `create()` - Show create form
  - `store()` - Create channel and handle MTProto auth flow
  - `edit()` - Show edit form
  - `update()` - Update channel config
  - `assign()` - Show assignment UI
  - `storeAssignments()` - Save assignments
  - `removeAssignment()` - Remove specific assignment
  - `authenticate()` - Handle MTProto phone/code auth
  - `destroy()` - Delete channel and cleanup

### FR-3: Assignment Management Service

**Priority:** HIGH

**Details:**
- Service: `Addons\MultiChannelSignalAddon\App\Services\ChannelAssignmentService`
- Methods:
  - `assignToUsers(ChannelSource $channel, array $userIds): void`
  - `assignToPlans(ChannelSource $channel, array $planIds): void`
  - `setGlobal(ChannelSource $channel, bool $enabled): void`
  - `getRecipients(ChannelSource $channel): Collection` - Returns all users who should receive signals
  - `removeUserAssignment(ChannelSource $channel, int $userId): void`
  - `removePlanAssignment(ChannelSource $channel, int $planId): void`
  - `clearGlobal(ChannelSource $channel): void`

### FR-4: Signal Distribution Service

**Priority:** HIGH

**Details:**
- Extend: `Addons\MultiChannelSignalAddon\App\Services\AutoSignalService`
- New method: `distributeToRecipients(Signal $signal, ChannelSource $channelSource): void`
- Logic:
  1. Get all recipients via `ChannelAssignmentService::getRecipients()`
  2. For each recipient:
     - Determine target plan(s)
     - Create signal copy or attach to plan
     - Prevent duplicates via message_hash + user_id check
  3. Queue distribution job for async processing

### FR-5: MTProto Session Management

**Priority:** MEDIUM

**Details:**
- Session files stored in: `storage/app/madelineproto/admin/{channel_id}.session`
- Update `TelegramMtprotoAdapter` to accept session path override
- Admin authentication flow accessible from backend UI
- Session cleanup on channel deletion

### FR-6: Admin UI Views

**Priority:** HIGH

**Details:**
- View namespace: `multi-channel-signal-addon::backend.channel-forwarding`
- Views:
  - `index.blade.php` - Channel list with assignments summary
  - `create.blade.php` - Create form (MTProto focused)
  - `edit.blade.php` - Edit form
  - `assign.blade.php` - Assignment management UI
  - `authenticate.blade.php` - MTProto phone/code auth
  - `view-samples.blade.php` - View sample messages and create parser patterns

### FR-6.1: Enhanced View Samples UI with jQuery AJAX

**Priority:** MEDIUM  
**Status:** IMPLEMENTED  
**Date:** 2025-01-27

**Details:**
- View Samples endpoint (`admin/channel-forwarding/{id}/view-samples`) enhanced with full jQuery AJAX implementation
- **Features:**
  - Channel selector changes load messages via AJAX (no page reload)
  - Limit selector changes load messages via AJAX
  - Refresh button loads messages via AJAX with loading states
  - Smooth fade-in animations for message updates
  - Loading overlays with spinners during AJAX requests
  - URL updates without page reload (History API)
  - Toast notifications for success/error (if toastr available)
  - Message selection with smooth scrolling and visual feedback
  - Form submission via AJAX with loading states
  - Pattern type changes with slide transitions
- **Technical Implementation:**
  - Controller supports AJAX requests via `$request->ajax()` check
  - Returns JSON response for AJAX, full view for normal requests
  - jQuery event delegation for dynamic content
  - Proper HTML escaping for security
  - Error handling for AJAX failures
  - Dynamic event rebinding after content updates

### FR-7: Menu Integration

**Priority:** MEDIUM

**Details:**
- Add "Admin Channels" menu item under "Signal tools" in admin sidebar
- Only visible to super admins
- Link to `/admin/channels`

### FR-8: Access Control

**Priority:** HIGH

**Details:**
- All admin channel routes protected by `admin` middleware
- Additional check: Only super admins can access
- User channel listings exclude admin-owned channels (`where('is_admin_owned', 0)`)

## Non-Functional Requirements

### NFR-1: Performance
- Assignment queries optimized with proper indexes
- Signal distribution via queue jobs (non-blocking)
- Batch processing for global assignments

### NFR-2: Security
- Admin MTProto sessions stored securely
- Assignment changes logged for audit
- Only super admins can manage admin channels

### NFR-3: Usability
- Clear assignment UI with search/filter
- Assignment summary visible in channel list
- Confirmation dialogs for destructive actions
- Smooth, responsive UI interactions without page reloads
- Loading states and visual feedback for all async operations
- Smooth animations and transitions for better UX

## Edge Cases

1. **User Deleted:** User assigned to channel is deleted
   - **Handling:** Remove assignment automatically or on next distribution attempt

2. **Plan Deleted:** Plan assigned to channel is deleted
   - **Handling:** Remove assignment, notify admin

3. **Global + Specific Assignments:** Channel has both global and specific assignments
   - **Handling:** Global takes precedence, specific assignments ignored (or merge logic)

4. **Duplicate Signals:** Same message from admin channel reaches user via multiple paths
   - **Handling:** Dedupe by message_hash + user_id + channel_source_id

5. **MTProto Session Expired:** Admin session becomes invalid
   - **Handling:** Mark channel as error, require re-authentication

## Data Model

### ChannelSource Extensions
```php
// New fillable fields
'is_admin_owned' => boolean,
'scope' => enum('user', 'plan', 'global'),

// Relationships
public function assignedUsers() {
    return $this->belongsToMany(User::class, 'channel_source_users');
}

public function assignedPlans() {
    return $this->belongsToMany(Plan::class, 'channel_source_plans');
}
```

### New Pivot Tables
- `channel_source_users`: channel_source_id, user_id
- `channel_source_plans`: channel_source_id, plan_id

## Dependencies

### Internal
- Existing ChannelSource model
- TelegramMtprotoService
- AutoSignalService
- Signal model
- Plan model
- User model

### External
- MadelineProto library (already required)
- Laravel Queue system

## Success Criteria

- [x] Admin can create MTProto channel from admin panel
- [x] Admin can assign channel to users/plans/global
- [x] Signals from admin channels distributed to assigned recipients
- [x] No duplicate signals for same recipient
- [x] Admin channel management UI fully functional
- [x] User channels unaffected by admin channels
- [x] View Samples endpoint uses jQuery AJAX for smooth interactions
- [x] Loading states and animations implemented for better UX

## Change Log

### 2025-01-27: Enhanced View Samples UI with jQuery AJAX
- **Changed:** View Samples endpoint (`viewSampleMessages`) now fully uses jQuery AJAX
- **Impact:** Eliminated page reloads, improved user experience with smooth transitions
- **Technical Details:**
  - Channel selector changes load messages via AJAX
  - Limit selector changes load messages via AJAX  
  - Refresh button uses AJAX with loading states
  - Smooth fade-in animations for content updates
  - Loading overlays during AJAX requests
  - URL updates via History API without reload
  - Toast notifications for user feedback
  - Message selection with smooth scrolling
  - Form submission via AJAX
  - Pattern type changes with slide transitions
- **Files Modified:**
  - `resources/views/backend/channel-forwarding/view-samples.blade.php`
  - Controller already supported AJAX via `$request->ajax()` check

