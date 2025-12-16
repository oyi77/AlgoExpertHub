# Feature Brief: Multi-Channel Signal Addon

**Created:** 2025-01-28
**Status:** ACTIVE
**Version:** 2.0

## Quick Summary

Automatically forward messages from external channels (Telegram, APIs, websites, RSS feeds) into the system as signals. Supports admin-owned channels with assignment to users/plans, AI-powered parsing, and comprehensive analytics.

## User Story

**As a** user/admin  
**I want to** connect external channels and automatically convert messages to signals  
**So that** I can review, edit, and publish signals without manual entry

## Key Requirements

### Core Features
- ✅ Multi-channel support (Telegram Bot, Telegram MTProto, API, Web Scrape, RSS)
- ✅ Two-tier architecture (Signal Sources + Channel Forwarding)
- ✅ Admin-owned channels with assignment system (users/plans/global)
- ✅ Message parsing (Regex → Pattern Templates → AI Fallback)
- ✅ Auto-signal creation with confidence scoring
- ✅ Admin review interface
- ✅ Signal analytics and reporting
- ✅ Pattern template management

### Channel Types
1. **Telegram Bot** - Via bot token (webhook/long polling)
2. **Telegram MTProto** - Via user account authentication
3. **REST API** - Webhook-based integrations
4. **Web Scraping** - CSS selector/XPath support
5. **RSS/Atom** - Feed polling

### Architecture
- **Signal Sources**: Connection management (`/admin/signal-sources`, `/user/signal-sources`)
- **Channel Forwarding**: Assignment and forwarding (`/admin/channel-forwarding`, `/user/channel-forwarding`)

## Technical Stack

- **Backend**: Laravel 8, PHP 7.3/8.0
- **Queue**: Laravel Queue (database/Redis)
- **Parsing**: Regex, Pattern Templates, AI (OpenAI, Gemini)
- **Database**: MySQL with new tables for channels, messages, patterns, analytics
- **Integrations**: Telegram Bot API, MadelineProto, Guzzle HTTP, Goutte

## Quick Start

### For Admins
1. Go to `/admin/signal-sources` to create connections
2. Authenticate Telegram accounts if using MTProto
3. Go to `/admin/channel-forwarding` to select channels and assign to users/plans
4. Configure pattern templates at `/admin/pattern-templates`
5. Review auto-created signals at `/admin/channel-signals`
6. View analytics at `/admin/signal-analytics`

### For Users
1. Go to `/user/signal-sources` to create your own connections
2. View assigned channels at `/user/channel-forwarding`
3. Channels assigned to you will automatically forward signals

## Key Routes

**Admin:**
- `/admin/signal-sources` - Manage signal source connections
- `/admin/channel-forwarding` - Manage channel forwarding and assignments
- `/admin/channel-signals` - Review auto-created signals
- `/admin/pattern-templates` - Manage parsing patterns
- `/admin/signal-analytics` - View analytics

**User:**
- `/user/signal-sources` - Manage own signal sources
- `/user/channel-forwarding` - View assigned channels

## Implementation Status

- ✅ **Core Infrastructure**: Complete
- ✅ **All Channel Types**: Complete
- ✅ **Message Processing**: Complete
- ✅ **Signal Management**: Complete
- ✅ **Admin Interface**: Complete
- ✅ **User Interface**: Complete
- ✅ **Analytics**: Complete
- ⏳ **Testing**: In progress
- ⏳ **Documentation**: In progress

## Files Reference

- **Full Specification**: `spec.md`
- **Technical Plan**: `plan.md`
- **Task Breakdown**: `tasks.md`
- **Research**: `research.md`
- **Evolution Log**: `EVOLUTION.md`

## Notes

- This addon has evolved significantly beyond the original spec (see `EVOLUTION.md`)
- Currently at version 2.0 with two-tier architecture
- Supports admin ownership model with channel assignment
- Includes AI-powered parsing as fallback
- Full analytics and reporting system implemented

