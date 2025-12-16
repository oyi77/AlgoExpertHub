# Task Breakdown: Admin-Managed Global Channels

**Created:** 2025-11-11
**Last Updated:** 2025-01-27
**Status:** ACTIVE

## Phase 1: Database Schema

### Task 1.1: Create Migration for channel_sources Extension
**Estimate:** 30 minutes
**Dependencies:** None
**Details:**
- Add `is_admin_owned` boolean column
- Add `scope` enum column
- Add index on `is_admin_owned`
- Make `user_id` nullable

### Task 1.2: Create Migration for channel_source_users Pivot
**Estimate:** 20 minutes
**Dependencies:** Task 1.1
**Details:**
- Create pivot table
- Add foreign keys
- Add unique constraint

### Task 1.3: Create Migration for channel_source_plans Pivot
**Estimate:** 20 minutes
**Dependencies:** Task 1.1
**Details:**
- Create pivot table
- Add foreign keys
- Add unique constraint

## Phase 2: Model Extensions

### Task 2.1: Extend ChannelSource Model
**Estimate:** 1 hour
**Dependencies:** Phase 1 complete
**Details:**
- Add `is_admin_owned` and `scope` to fillable
- Add `assignedUsers()` relationship
- Add `assignedPlans()` relationship
- Add `scopeAdminOwned()` scope
- Add `scopeUserOwned()` scope
- Add `isAdminOwned()` helper method

## Phase 3: Service Layer

### Task 3.1: Create ChannelAssignmentService
**Estimate:** 2 hours
**Dependencies:** Task 2.1
**Details:**
- Implement `assignToUsers()`
- Implement `assignToPlans()`
- Implement `setGlobal()`
- Implement `getRecipients()` - complex logic for user/plan/global
- Implement removal methods
- Add validation and error handling

### Task 3.2: Extend AutoSignalService
**Estimate:** 1.5 hours
**Dependencies:** Task 3.1
**Details:**
- Add `distributeToRecipients()` method
- Integrate with ProcessChannelMessage job
- Handle duplicate detection
- Plan attachment logic

### Task 3.3: Create DistributeAdminSignalJob
**Estimate:** 1 hour
**Dependencies:** Task 3.2
**Details:**
- Queue job for async distribution
- Batch processing logic
- Error handling and logging

## Phase 4: Controller & Routes

### Task 4.1: Create AdminChannelController
**Estimate:** 3 hours
**Dependencies:** Phase 3 complete
**Details:**
- Implement `index()` - list with assignments
- Implement `create()` - show form
- Implement `store()` - create channel
- Implement `edit()` - show edit form
- Implement `update()` - update channel
- Implement `assign()` - assignment UI
- Implement `storeAssignments()` - save assignments
- Implement `removeUserAssignment()`
- Implement `removePlanAssignment()`
- Implement `authenticate()` - MTProto auth
- Implement `destroy()` - delete with cleanup

### Task 4.2: Add Admin Routes
**Estimate:** 30 minutes
**Dependencies:** Task 4.1
**Details:**
- Add routes to `routes/admin.php` or addon routes
- Apply middleware
- Add route names

## Phase 5: UI Views

### Task 5.1: Create Admin Channel Index View
**Estimate:** 1.5 hours
**Dependencies:** Task 4.1
**Details:**
- List all admin channels
- Show assignment summary
- Status indicators
- Action buttons

### Task 5.2: Create Admin Channel Create/Edit Views
**Estimate:** 2 hours
**Dependencies:** Task 4.1
**Details:**
- Form for MTProto credentials
- Authentication flow UI
- Default settings (plan, market, timeframe)
- Error handling display

### Task 5.3: Create Assignment Management View
**Estimate:** 2 hours
**Dependencies:** Task 4.1
**Details:**
- User multi-select with search
- Plan multi-select
- Global toggle
- Assignment summary display
- Remove assignment buttons

### Task 5.4: Create MTProto Authentication View
**Estimate:** 1 hour
**Dependencies:** Task 4.1
**Details:**
- Phone number input
- Verification code input
- Step indicators
- Error messages

### Task 5.5: Enhance View Samples UI with jQuery AJAX
**Estimate:** 2 hours
**Status:** COMPLETED (2025-01-27)
**Dependencies:** Task 5.1
**Details:**
- Convert channel selector to AJAX loading
- Convert limit selector to AJAX loading
- Convert refresh button to AJAX with loading states
- Add loading overlays and spinners
- Implement smooth fade-in animations
- Add toast notifications for feedback
- Implement smooth scrolling for message selection
- Convert form submission to AJAX
- Add pattern type change transitions
- Implement proper error handling
- Add URL updates via History API

## Phase 6: MTProto Integration

### Task 6.1: Update TelegramMtprotoAdapter for Admin Sessions
**Estimate:** 1 hour
**Dependencies:** Task 2.1
**Details:**
- Modify session path for admin channels
- Update session storage location
- Handle admin namespace

### Task 6.2: Update TelegramMtprotoService for Admin Flow
**Estimate:** 1 hour
**Dependencies:** Task 6.1
**Details:**
- Support admin channel creation
- Handle admin authentication flow
- Session cleanup for admin channels

## Phase 7: Menu Integration

### Task 7.1: Add Admin Channels Menu Item
**Estimate:** 30 minutes
**Dependencies:** Task 4.2
**Details:**
- Add to admin sidebar
- Under "Signal tools" section
- Super admin only visibility

## Phase 8: Access Control & Filtering

### Task 8.1: Update User Channel Queries
**Estimate:** 30 minutes
**Dependencies:** Task 2.1
**Details:**
- Exclude admin channels from user listings
- Update ChannelController queries

### Task 8.2: Add Super Admin Middleware Check
**Estimate:** 30 minutes
**Dependencies:** Task 4.1
**Details:**
- Check super admin in controller
- Return 403 if not authorized

## Phase 9: Testing & Refinement

### Task 9.1: Test Channel Creation Flow
**Estimate:** 1 hour
**Details:**
- Create admin channel
- MTProto authentication
- Verify session storage

### Task 9.2: Test Assignment Management
**Estimate:** 1 hour
**Details:**
- Assign to users
- Assign to plans
- Set global
- Remove assignments

### Task 9.3: Test Signal Distribution
**Estimate:** 1.5 hours
**Details:**
- Send test message
- Verify distribution to recipients
- Check duplicate prevention
- Verify plan attachments

### Task 9.4: Integration Testing
**Estimate:** 1 hour
**Details:**
- End-to-end flow
- Error scenarios
- Edge cases

## Total Estimated Time: ~24 hours (including Task 5.5)

## Implementation Order

1. Phase 1 (Database) - Foundation
2. Phase 2 (Models) - Data layer
3. Phase 3 (Services) - Business logic
4. Phase 4 (Controllers) - API layer
5. Phase 5 (Views) - UI layer
6. Phase 6 (MTProto) - Integration
7. Phase 7 (Menu) - Navigation
8. Phase 8 (Security) - Access control
9. Phase 9 (Testing) - Validation

## Evolution Log

### 2025-01-27: Enhanced View Samples UI with jQuery AJAX
- **Task:** Task 5.5 - Enhance View Samples UI with jQuery AJAX
- **Status:** COMPLETED
- **Changes:**
  - Converted all page reloads to AJAX requests
  - Added loading states and overlays
  - Implemented smooth animations and transitions
  - Added toast notifications for user feedback
  - Improved overall user experience
- **Impact:** Significantly improved UX by eliminating page reloads and adding smooth interactions
- **Files Modified:**
  - `main/addons/multi-channel-signal-addon/resources/views/backend/channel-forwarding/view-samples.blade.php`

