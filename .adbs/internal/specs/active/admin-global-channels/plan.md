# Technical Plan: Admin-Managed Global Channels

**Created:** 2025-11-11
**Last Updated:** 2025-01-27
**Status:** ACTIVE

## Architecture Overview

The feature extends the existing Multi-Channel Signal Addon with admin-level channel management and dynamic assignment capabilities. The architecture follows Laravel best practices with clear separation of concerns.

## Component Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Admin Backend UI                          │
│  (AdminChannelController + Views)                           │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              Channel Assignment Service                      │
│  (ChannelAssignmentService)                                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              ChannelSource Model (Extended)                   │
│  + Relationships: assignedUsers, assignedPlans             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│         Signal Distribution Service                         │
│  (AutoSignalService::distributeToRecipients)                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              Queue Jobs                                      │
│  (DistributeAdminSignalJob)                                 │
└─────────────────────────────────────────────────────────────┘
```

## Database Schema Changes

### Migration 1: Extend channel_sources
```php
Schema::table('channel_sources', function (Blueprint $table) {
    $table->boolean('is_admin_owned')->default(0)->after('user_id');
    $table->enum('scope', ['user', 'plan', 'global'])->nullable()->after('is_admin_owned');
    $table->index('is_admin_owned');
});
```

### Migration 2: Create channel_source_users pivot
```php
Schema::create('channel_source_users', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('channel_source_id');
    $table->unsignedBigInteger('user_id');
    $table->timestamps();
    
    $table->foreign('channel_source_id')->references('id')->on('channel_sources')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->unique(['channel_source_id', 'user_id']);
});
```

### Migration 3: Create channel_source_plans pivot
```php
Schema::create('channel_source_plans', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('channel_source_id');
    $table->unsignedBigInteger('plan_id');
    $table->timestamps();
    
    $table->foreign('channel_source_id')->references('id')->on('channel_sources')->onDelete('cascade');
    $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
    $table->unique(['channel_source_id', 'plan_id']);
});
```

## Service Layer Design

### ChannelAssignmentService

**Responsibilities:**
- Manage user/plan/global assignments
- Calculate recipient lists
- Handle assignment removal

**Key Methods:**
```php
class ChannelAssignmentService {
    public function assignToUsers(ChannelSource $channel, array $userIds): void
    public function assignToPlans(ChannelSource $channel, array $planIds): void
    public function setGlobal(ChannelSource $channel, bool $enabled): void
    public function getRecipients(ChannelSource $channel): Collection
    public function removeUserAssignment(ChannelSource $channel, int $userId): void
    public function removePlanAssignment(ChannelSource $channel, int $planId): void
    public function clearGlobal(ChannelSource $channel): void
}
```

### Signal Distribution Flow

```
Admin Channel Message Received
    ↓
ProcessChannelMessage Job (existing)
    ↓
Parse Message → Create Signal Draft
    ↓
Check if ChannelSource is admin-owned
    ↓
YES → DistributeAdminSignalJob
    ↓
Get Recipients (users/plans/global)
    ↓
For Each Recipient:
    - Determine target plan(s)
    - Check duplicate (message_hash + user_id)
    - Attach signal to plan OR create user-specific copy
    ↓
Signal Available to Recipients
```

## Controller Design

### ChannelForwardingController (Admin)

**Routes:**
- `GET /admin/channel-forwarding` - Index
- `GET /admin/channel-forwarding/create` - Create form
- `POST /admin/channel-forwarding` - Store
- `GET /admin/channel-forwarding/{id}` - Show details
- `GET /admin/channel-forwarding/{id}/edit` - Edit form
- `PUT /admin/channel-forwarding/{id}` - Update
- `GET /admin/channel-forwarding/{id}/view-samples` - View sample messages (supports AJAX)
- `POST /admin/channel-forwarding/{id}/store-parser` - Store parser pattern
- `POST /admin/channel-forwarding/{id}/test-parser` - Test parser pattern
- `GET /admin/channel-forwarding/{id}/select-channel` - Select Telegram channels
- `POST /admin/channel-forwarding/{id}/select-channel` - Save selected channels
- `GET /admin/channel-forwarding/{id}/assign` - Assignment UI
- `POST /admin/channel-forwarding/{id}/assign` - Save assignments
- `DELETE /admin/channel-forwarding/{id}/users/{userId}` - Remove user assignment
- `DELETE /admin/channel-forwarding/{id}/plans/{planId}` - Remove plan assignment
- `DELETE /admin/channel-forwarding/{id}` - Destroy

**AJAX Support:**
- `viewSampleMessages()` method checks `$request->ajax()` and returns JSON for AJAX requests
- JSON response structure: `{success: bool, messages: array, error: string|null}`

## Model Extensions

### ChannelSource Model

**New Relationships:**
```php
public function assignedUsers() {
    return $this->belongsToMany(User::class, 'channel_source_users');
}

public function assignedPlans() {
    return $this->belongsToMany(Plan::class, 'channel_source_plans');
}
```

**New Scopes:**
```php
public function scopeAdminOwned($query) {
    return $query->where('is_admin_owned', 1);
}

public function scopeUserOwned($query) {
    return $query->where('is_admin_owned', 0);
}
```

## Queue Job Design

### DistributeAdminSignalJob

**Purpose:** Distribute signal from admin channel to assigned recipients

**Logic:**
1. Get channel source
2. Get all recipients via ChannelAssignmentService
3. For each recipient:
   - Determine plan(s) to attach
   - Check for duplicate signal
   - Attach signal to plan or create copy
4. Log distribution results

## Security Considerations

1. **Access Control:**
   - All routes protected by `admin` middleware
   - Additional super admin check in controller

2. **Session Security:**
   - MTProto sessions stored in admin namespace
   - File permissions restricted

3. **Data Isolation:**
   - Admin channels excluded from user queries
   - User channels excluded from admin queries (unless admin-owned)

## Performance Optimizations

1. **Eager Loading:**
   - Load assignments with channels in index
   - Load relationships in distribution job

2. **Queue Processing:**
   - Distribution happens asynchronously
   - Batch processing for large recipient lists

3. **Database Indexes:**
   - Index on `is_admin_owned`
   - Composite indexes on pivot tables

4. **Frontend Performance:**
   - AJAX-based loading eliminates full page reloads
   - Smooth animations and transitions improve perceived performance
   - Loading states provide immediate feedback
   - URL updates via History API maintain browser history

## Testing Strategy

1. **Unit Tests:**
   - ChannelAssignmentService methods
   - Signal distribution logic
   - Model relationships

2. **Feature Tests:**
   - Admin channel CRUD
   - Assignment management
   - Signal distribution flow

3. **Integration Tests:**
   - End-to-end signal distribution
   - MTProto authentication flow

## Deployment Considerations

1. **Migration Order:**
   - Run migrations in sequence
   - No data loss (additive changes)

2. **Backward Compatibility:**
   - Existing user channels unaffected
   - Default `is_admin_owned = 0` maintains current behavior

3. **Rollback Plan:**
   - Migrations reversible
   - Feature can be disabled via addon module toggle

## UI/UX Enhancements (2025-01-27)

### jQuery AJAX Implementation

**View Samples Endpoint Enhancements:**
- Full jQuery AJAX implementation for smooth interactions
- No page reloads for channel/limit changes
- Loading overlays with spinners
- Smooth fade-in animations
- Toast notifications for user feedback
- Smooth scrolling for message selection
- Form submission via AJAX

**Technical Details:**
- Uses jQuery `$.ajax()` for all async operations
- Event delegation for dynamic content
- Proper HTML escaping for XSS prevention
- Error handling with user-friendly messages
- History API for URL updates without reload

