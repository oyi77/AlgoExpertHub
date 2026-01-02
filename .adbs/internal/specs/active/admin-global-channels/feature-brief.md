# Feature Brief: Admin-Managed Global Channels

**Prepared:** 2025-11-11  
**Owner:** GPT-5 Codex  
**Status:** Draft

## Context / Problem Statement

The current multi-channel addon only allows end users to register channel sources (Telegram bot, API, web scrape, RSS, MTProto). Admins need the ability to connect MTProto (Telegram user) channels centrally, then distribute the resulting signals to target cohorts (specific users, entire plans, or globally). Without admin-level channels, staff must duplicate connections per user and cannot reuse trusted channel feeds.

## Goals & Outcomes

- Enable admins to create and manage channel sources (with emphasis on Telegram MTProto) from the admin panel.
- Allow an admin channel to be assigned to:
  - One or more individual users.
  - One or more subscription plans.
  - All current and future users (global).
- Ensure signals parsed from admin channels are forwarded automatically to the assigned recipients’ signal queues/plans.
- Preserve secure handling of MTProto credentials/sessions managed solely by admins.

## Non-Goals

- Sharing admin channels with end users for credential management.
- Replacing existing user channel management flows.
- Implementing new parsing logic (reuse current parsing pipeline).

## Key Requirements & Ideas

1. **Admin UI**
   - Dedicated backend section under `Signal tools` (e.g., `Admin Channels`) listing all admin-managed channels with status, assignments, and last-processed timestamps.
   - Create/edit wizard mirroring user channel forms but scoped to admin.
   - Assignment UI to select targets (`users`, `plans`, `global`). Targets should support multi-select and display resulting coverage counts.

2. **Data Model**
   - Extend `channel_sources` to flag `is_admin_owned` and support nullable `user_id` for global ownership.
   - Introduce pivot tables:
     - `channel_source_user` (channel → user assignments).
     - `channel_source_plan` (channel → plan assignments).
   - Support `scope = global` to broadcast to all users/plans (including future ones).

3. **MTProto Session Handling**
   - Reuse existing MTProto service but ensure session files stored under admin namespace (e.g., `storage/app/madelineproto/admin/{channel_id}.session`).
   - Admin-only authentication flow similar to user flow, accessible via backend UI.

4. **Signal Distribution**
   - When an admin-owned channel generates a signal:
     - For user assignments: attach to user-specific default plans or a designated admin plan mapping.
     - For plan assignments: attach to those plans automatically.
     - For global scope: deliver to all active plans or a configurable default plan.
   - Avoid duplicate signals for users receiving the same message via multiple assignments (need dedupe logic keyed by channel + message hash + recipient).

5. **Access Control**
   - Restrict admin channel CRUD to super admins.
   - Hide admin-owned channels from standard user channel listings.

6. **Observability**
   - Track assignment metadata, processing counts, and errors per channel.
   - Provide quick actions to pause channel or remove specific assignments.

## Risks / Open Questions

- How should conflicts be handled when both user and admin channels feed the same plan/user?
- Should admin channels support auto-publish thresholds per assignment, or single global threshold?
- Is there a need for audit logs when admin adjusts assignments?
- Storage and security implications for MTProto sessions managed centrally.

## Next Steps

1. Produce detailed specification (`/specify admin-global-channels`) covering schema changes, services, and job updates.
2. Draft solution architecture (`/plan admin-global-channels`) including sequence diagrams for ingestion and distribution.
3. Break down implementation tasks (`/tasks admin-global-channels`).


