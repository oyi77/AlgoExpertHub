# Specification: Multi-Channel Signal Addon

**Created:** 2025-01-27
**Last Updated:** 2025-01-28
**Status:** ACTIVE
**Version:** 2.0

## Overview

The Multi-Channel Signal Addon enables users to automatically forward messages from external channels (Telegram groups/channels, APIs, websites, RSS feeds) into the system as signals. This feature acts as a "mirror" system, allowing users to connect their subscribed channels and automatically convert incoming messages into signals that can be reviewed, edited, and published through the existing signal management system.

The addon supports multiple channel types:
- **Telegram Channels/Groups** - Via Telegram Bot API (bot token)
- **Telegram MTProto** - Via user account authentication (phone number)
- **REST APIs** - Webhook-based integrations
- **Web Scraping** - Public websites and forums
- **RSS/Atom Feeds** - News feeds and blog posts

The addon has evolved into a two-tier architecture:
1. **Signal Sources** - Connection management (Telegram, API, Web Scrape, RSS)
2. **Channel Forwarding** - Channel selection, assignment, and message forwarding

**Key Features:**
- Admin-owned channels can be assigned to users/plans globally
- User-owned channels are private to the user
- AI-powered message parsing (OpenAI, Gemini) as fallback
- Pattern template management system
- Signal analytics and reporting
- Channel assignment system (user, plan, global scope)

All automatically created signals are initially created as drafts (unpublished) for admin review, with optional auto-publishing based on confidence scores.

## User Stories

### Story 1: Add Telegram Channel Source
**As a** user (with active subscription)
**I want to** connect a Telegram channel that I'm subscribed to
**So that** messages from that channel are automatically forwarded to my system as signals

**Acceptance Criteria:**
- [ ] User can add Telegram channel by providing bot token and channel username/ID
- [ ] System validates bot token and channel accessibility
- [ ] Bot is added to channel (user receives instructions if bot needs admin approval)
- [ ] Channel connection status is displayed (active/pending/error)
- [ ] User can pause/resume channel processing
- [ ] User receives notification when channel connection fails

### Story 2: Add API-Based Channel Source
**As a** user (with active subscription)
**I want to** connect an external API via webhook
**So that** signals from that API are automatically received and processed

**Acceptance Criteria:**
- [ ] User can add API source by providing webhook URL and authentication credentials
- [ ] System generates unique webhook endpoint for receiving API messages
- [ ] System validates webhook signature/authentication
- [ ] API messages are received and queued for processing
- [ ] User can configure webhook endpoint and view incoming message logs

### Story 3: Add Web Scraping Source
**As a** user (with active subscription)
**I want to** connect a public website for scraping
**So that** messages from that website are automatically scraped and converted to signals

**Acceptance Criteria:**
- [ ] User can add web scraping source by providing URL and CSS selectors/XPath
- [ ] System validates URL accessibility and selector validity
- [ ] System scrapes content at configurable intervals (minimum 30 seconds)
- [ ] Only new content (not previously seen) is processed
- [ ] User receives warnings about ethical/legal considerations
- [ ] System respects robots.txt if present

### Story 4: Add RSS Feed Source
**As a** user (with active subscription)
**I want to** connect an RSS/Atom feed
**So that** new feed items are automatically converted to signals

**Acceptance Criteria:**
- [ ] User can add RSS feed by providing feed URL
- [ ] System validates feed format and accessibility
- [ ] System polls feed at configurable intervals (minimum 5 minutes)
- [ ] Only new feed items are processed
- [ ] Feed metadata (title, description, published date) is captured

### Story 5: Automatic Signal Creation from Messages
**As a** system
**I want to** automatically parse incoming messages and create signal drafts
**So that** admins can review and publish signals without manual entry

**Acceptance Criteria:**
- [ ] System attempts to parse message content using regex patterns
- [ ] Extracts: currency pair, entry price, stop loss, take profit, direction, timeframe
- [ ] Creates draft signal (is_published = 0) for admin review
- [ ] Links signal to channel source for tracking
- [ ] Stores original message for reference
- [ ] Handles parsing failures gracefully (queue for manual review)

### Story 6: Review Auto-Created Signals
**As an** admin
**I want to** review signals created from channel sources
**So that** I can validate, edit, and publish them

**Acceptance Criteria:**
- [ ] Admin sees list of auto-created signals (drafts from channels)
- [ ] Admin can view original message and parsed data
- [ ] Admin can edit signal fields before publishing
- [ ] Admin can approve/publish signal
- [ ] Admin can reject signal (with reason)
- [ ] Admin can configure auto-publish for high-confidence parses

### Story 7: Configure Message Parsing Patterns
**As a** user (with active subscription)
**I want to** configure custom parsing patterns for my channels
**So that** messages are parsed correctly according to my channel's format

**Acceptance Criteria:**
- [ ] User can define regex patterns for extracting signal data
- [ ] User can test patterns against sample messages
- [ ] User can set default values (market, timeframe, plan assignment)
- [ ] User can save multiple pattern templates
- [ ] System uses patterns in priority order

### Story 8: Monitor Channel Status and Errors
**As a** user (with active subscription)
**I want to** monitor my connected channels
**So that** I know if they're working or need attention

**Acceptance Criteria:**
- [ ] User sees channel status (active, paused, error)
- [ ] User sees last processed timestamp
- [ ] User sees error count and last error message
- [ ] User receives notifications for persistent errors
- [ ] User can view message processing logs
- [ ] User can manually retry failed messages

### Story 9: Duplicate Detection
**As a** system
**I want to** detect duplicate messages
**So that** the same signal isn't created multiple times

**Acceptance Criteria:**
- [ ] System hashes message content + timestamp
- [ ] System checks for existing signals with same hash in last 24 hours
- [ ] System skips duplicate messages
- [ ] System logs duplicate detection for monitoring

### Story 10: Rate Limiting and Throttling
**As a** system
**I want to** respect platform rate limits
**So that** channels don't get blocked or banned

**Acceptance Criteria:**
- [ ] Telegram channels respect 30 messages/second limit
- [ ] Web scraping respects configurable delay (default 1-2 seconds)
- [ ] API requests respect provider-specific limits
- [ ] System implements exponential backoff for retries
- [ ] System queues messages when rate limits are hit

## Functional Requirements

### FR-1: Channel Source Management
**Description:** Users can create, read, update, and delete channel sources. Each channel source belongs to a user and has a type (telegram, api, web_scrape, rss), configuration (JSON), and status.

**Priority:** HIGH
**Dependencies:** User authentication, Database schema

**Details:**
- Channel source fields: id, user_id, name, type, config (JSON), status, last_processed_at, error_count, last_error
- User can have multiple channel sources
- Channel sources can be paused/resumed
- Channel sources are automatically paused after N consecutive errors (configurable, default 10)

### FR-2: Telegram Bot Integration
**Description:** Integrate with Telegram Bot API to receive messages from channels/groups where bot is added.

**Priority:** HIGH
**Dependencies:** Telegram Bot API, Webhook endpoint or long polling

**Details:**
- Support webhook mode (production) and long polling (development)
- Store update_id to avoid duplicate processing
- Handle bot removal from channel gracefully
- Store bot token encrypted in channel source config
- Process messages via queue jobs

### FR-3: REST API Webhook Integration
**Description:** Provide webhook endpoints for external APIs to send signal data.

**Priority:** MEDIUM
**Dependencies:** Webhook endpoint, Signature verification

**Details:**
- Generate unique webhook URL per API source
- Verify webhook signatures (provider-specific)
- Accept JSON payloads with signal data
- Queue incoming webhook payloads for processing
- Support retry logic for failed webhooks

### FR-4: Web Scraping Engine
**Description:** Scrape public websites for signal content using configurable selectors.

**Priority:** MEDIUM
**Dependencies:** Goutte, Guzzle HTTP client

**Details:**
- Support CSS selectors and XPath
- Implement rate limiting (configurable delay)
- Respect robots.txt
- Cache responses to reduce requests
- Handle HTML structure changes gracefully
- Use queue jobs with delays

### FR-5: RSS/Atom Feed Parser
**Description:** Parse RSS and Atom feeds for new signal content.

**Priority:** LOW
**Dependencies:** XML parser, Scheduled tasks

**Details:**
- Parse RSS 2.0 and Atom 1.0 formats
- Extract title, description, published date, link
- Poll feeds at configurable intervals (default 10 minutes)
- Track last processed item ID/timestamp
- Use Laravel scheduled commands

### FR-6: Message Parsing Pipeline
**Description:** Parse incoming messages to extract signal data using multiple parsers in sequence.

**Priority:** HIGH
**Dependencies:** Regex patterns, Signal model

**Details:**
- Implement adapter pattern with multiple parsers
- Parsers: RegexPatternParser, UserDefinedPatternParser, ManualReviewParser
- Extract: currency_pair, open_price, sl, tp, direction, timeframe, market
- Return confidence score (0-100)
- If all parsers fail, queue for manual review

### FR-7: Signal Creation Service
**Description:** Create signal drafts from parsed message data.

**Priority:** HIGH
**Dependencies:** SignalService, Signal model, CurrencyPair, TimeFrame, Market models

**Details:**
- Map parsed data to Signal model fields
- Validate currency pair exists (create if not, or use default)
- Validate timeframe exists (create if not, or use default)
- Validate market exists (create if not, or use default)
- Set is_published = 0 (draft)
- Set auto_created = 1
- Link to channel_source_id
- Store message_hash for duplicate detection
- Assign to user's default plan or prompt for selection

### FR-8: Admin Review Interface
**Description:** Admin interface for reviewing, editing, and publishing auto-created signals.

**Priority:** HIGH
**Dependencies:** SignalController, Admin authentication

**Details:**
- List all auto-created draft signals
- Filter by channel source, date, status
- View original message and parsed data
- Edit signal fields before publishing
- Approve and publish signal
- Reject signal (delete or mark as rejected)
- Bulk actions (approve/reject multiple)

### FR-9: Message Logging and Tracking
**Description:** Log all incoming messages for debugging, duplicate detection, and audit trail.

**Priority:** MEDIUM
**Dependencies:** Database schema

**Details:**
- Store raw message, message hash, parsed data, signal_id, status
- Status: pending, processed, failed, duplicate
- Retain logs for configurable period (default 30 days)
- Searchable by channel source, date, status

### FR-10: Auto-Publish Configuration
**Description:** Optionally auto-publish signals with high confidence scores.

**Priority:** LOW
**Dependencies:** Parsing confidence scores, Signal creation

**Details:**
- User/admin can set confidence threshold (default: 90%)
- Signals with confidence >= threshold are auto-published
- Signals below threshold remain drafts for review
- Log auto-publish decisions

### FR-11: Channel Source Status Monitoring
**Description:** Monitor and report channel source health and errors.

**Priority:** MEDIUM
**Dependencies:** Channel source model, Error logging

**Details:**
- Track last_processed_at timestamp
- Track error_count
- Store last_error message
- Auto-pause after N consecutive errors
- Send notifications for persistent errors
- Display status dashboard

### FR-12: User Configuration for Channels
**Description:** Users can configure channel-specific settings.

**Priority:** MEDIUM
**Dependencies:** Channel source model

**Details:**
- Default plan assignment for signals
- Default market assignment
- Default timeframe assignment
- Custom parsing patterns
- Auto-publish settings
- Notification preferences

### FR-13: Addon Lifecycle Management
**Description:** Super admins can manage addon packages and the modules they expose via a dedicated backend interface.

**Priority:** HIGH
**Dependencies:** Addon manifest schema, Admin backend

**Details:**
- Manage Addons page must provide only three capabilities: upload addon packages, enable/disable addons, and enable/disable individual modules declared in a manifest.
- Addon manifest files (`addon.json`) must declare module metadata (`key`, `name`, `description`, `targets`, `enabled`) to drive UI and runtime behavior.
- Toggling addon or module status updates the manifest immediately and controls service provider bootstrapping (routes, menus, background jobs).
- Upload flow accepts ZIP archives containing an addon root with `addon.json`, extracts into `addons/{name}`, and rejects conflicts or invalid packages gracefully.
- No additional management actions (e.g., direct navigation shortcuts) are exposed from this screen.

## Non-Functional Requirements

### NFR-1: Performance
- Message processing must not block web requests (use queues)
- Telegram webhook responses within 200ms
- Parse and create signal draft within 5 seconds
- Support processing 100+ messages per minute per channel
- Database queries optimized with proper indexes
- Cache channel configurations and currency pairs

### NFR-2: Security
- Encrypt API credentials and bot tokens in database
- Verify webhook signatures (Telegram, custom APIs)
- Validate and sanitize all parsed data
- Prevent SQL injection and XSS attacks
- Rate limit webhook endpoints
- IP whitelisting for webhooks (optional)
- HTTPS required for webhook endpoints

### NFR-3: Scalability
- Queue-based architecture for horizontal scaling
- Support 1000+ active channel sources
- Process messages concurrently via queue workers
- Use Redis for caching (optional but recommended)
- Database read replicas for high read volume
- Horizontal scaling of queue workers

### NFR-4: Usability
- Simple channel connection wizard (step-by-step)
- Clear error messages with actionable guidance
- Visual status indicators (green/yellow/red)
- Test parsing patterns before saving
- Preview parsed signal before creation
- Responsive design for mobile admin review

### NFR-5: Reliability
- 99.9% uptime for message processing
- Automatic retry for transient failures
- Graceful degradation (pause channels on persistent errors)
- Comprehensive error logging
- Alert admins for critical failures
- Message deduplication to prevent duplicates

### NFR-6: Maintainability
- Adapter pattern for easy addition of new channel types
- Modular parsing system
- Comprehensive logging for debugging
- Clear separation of concerns
- Follow Laravel best practices
- Documented code and APIs

## Edge Cases

1. **Bot Removed from Channel:** Telegram bot is removed from channel by admin
   - **Handling:** Detect via API error, mark channel as error, notify user, pause channel

2. **Website Structure Changed:** Scraped website changes HTML structure
   - **Handling:** Parser fails, increment error count, notify user after N failures, pause channel

3. **Invalid Currency Pair:** Message contains currency pair not in database
   - **Handling:** Use default pair, or create new pair, or queue for manual review (configurable)

4. **Conflicting Signals:** Same message parsed differently by multiple parsers
   - **Handling:** Use parser with highest confidence score, or first successful parser

5. **Rate Limit Exceeded:** Channel hits platform rate limit
   - **Handling:** Queue messages with delay, implement exponential backoff, notify user

6. **Message Format Not Recognized:** Message doesn't match any parsing pattern
   - **Handling:** Queue for manual review, store original message, allow admin to create pattern

7. **Channel Source Deleted:** User deletes channel source while messages are in queue
   - **Handling:** Cancel queued jobs, mark related signals as orphaned, allow cleanup

8. **User Subscription Expired:** User's subscription expires while channel is active
   - **Handling:** Pause all user's channels, notify user, allow reconnection after renewal

9. **Duplicate Message from Different Sources:** Same signal from multiple channels
   - **Handling:** Detect via message hash, create single signal, link to all sources

10. **Very Long Messages:** Message exceeds parsing or storage limits
    - **Handling:** Truncate or split message, parse available portion, flag for review

## Error Scenarios

### Error-1: Telegram Bot Token Invalid
**Scenario:** User provides invalid or expired bot token
**Expected Behavior:** 
- Validate token on connection attempt
- Display clear error message
- Prevent channel creation
- Provide troubleshooting steps

### Error-2: Channel Access Denied
**Scenario:** Bot doesn't have permission to access channel
**Expected Behavior:**
- Detect via API error (403 Forbidden)
- Display instructions for adding bot as admin
- Allow channel creation in "pending" status
- Retry connection periodically

### Error-3: Webhook Signature Mismatch
**Scenario:** Incoming webhook has invalid signature
**Expected Behavior:**
- Reject webhook request
- Log security event
- Return 401 Unauthorized
- Notify admin of suspicious activity

### Error-4: Parsing Failure
**Scenario:** Message cannot be parsed by any parser
**Expected Behavior:**
- Queue message for manual review
- Store original message
- Log parsing attempt
- Notify user if failure rate is high

### Error-5: Database Connection Failure
**Scenario:** Database is unavailable during signal creation
**Expected Behavior:**
- Retry job with exponential backoff
- Store message in queue
- Alert admin after N retries
- Graceful degradation (pause processing)

### Error-6: Currency Pair Not Found
**Scenario:** Parsed currency pair doesn't exist in database
**Expected Behavior:**
- Use default pair (configurable)
- Or create new pair automatically
- Or queue for manual review
- Log action taken

### Error-7: Invalid Price Data
**Scenario:** Parsed price is negative, zero, or unreasonably high
**Expected Behavior:**
- Validate price ranges (configurable min/max)
- Reject invalid prices
- Queue for manual review
- Log validation failure

### Error-8: Web Scraping Blocked
**Scenario:** Target website blocks scraping attempts
**Expected Behavior:**
- Detect via HTTP status codes (403, 429)
- Implement backoff strategy
- Pause channel after N failures
- Notify user with recommendations

### Error-9: Queue Worker Failure
**Scenario:** Queue worker crashes or becomes unavailable
**Expected Behavior:**
- Messages remain in queue
- New workers pick up jobs
- Monitor queue size
- Alert if queue grows too large

### Error-10: Memory Exhaustion
**Scenario:** Processing large messages or high volume causes memory issues
**Expected Behavior:**
- Set memory limits for queue jobs
- Process messages in chunks
- Implement garbage collection
- Monitor memory usage

## Success Criteria

- [ ] Users can successfully connect Telegram channels with 95% success rate
- [ ] System processes 1000+ messages per hour without errors
- [ ] Message parsing accuracy >= 80% (auto-created signals require minimal editing)
- [ ] Signal creation latency < 5 seconds from message receipt
- [ ] Admin review interface handles 100+ draft signals efficiently
- [ ] Zero duplicate signals created from same message
- [ ] Channel source uptime >= 99% (excluding user-initiated pauses)
- [ ] Webhook endpoint response time < 200ms
- [ ] All API credentials encrypted and secure
- [ ] Comprehensive error logging and monitoring in place

## Dependencies

### Internal Dependencies
- Existing Signal model and SignalService
- User authentication and authorization
- Plan subscription system
- CurrencyPair, TimeFrame, Market models
- Laravel Queue system (database driver)
- Admin panel and authentication

### External Dependencies
- Telegram Bot API
- Telegram MTProto (MadelineProto library)
- Guzzle HTTP Client (Laravel)
- Goutte (for web scraping, optional)
- Symfony DomCrawler (for parsing, optional)
- PHP XML extensions (for RSS parsing)
- Redis (optional, for caching and queues)
- OpenAI API (for AI parsing, optional)
- Google Gemini API (for AI parsing, optional)

### Third-Party Services
- Telegram Bot API (free)
- Telegram MTProto (via MadelineProto)
- OpenAI API (for AI parsing, requires API key)
- Google Gemini API (for AI parsing, requires API key)
- External APIs (user-provided)
- Web scraping targets (user-provided URLs)
- RSS/Atom feeds (user-provided URLs)

## Out of Scope

### Phase 1 (MVP) - Out of Scope (NOW IMPLEMENTED)
- [x] Telegram Client API (MTProto) - ✅ Implemented via MadelineProto
- [ ] Real-time message streaming (webhooks only for now)
- [x] Advanced NLP parsing (AI parsing via OpenAI/Gemini) - ✅ Implemented
- [ ] Multi-language support for parsing
- [x] Signal templates/formatting - ✅ Pattern templates implemented
- [x] User-to-user channel sharing - ✅ Via channel assignment system
- [x] Channel analytics and statistics - ✅ SignalAnalytics implemented
- [ ] Mobile app for channel management

### Future Considerations
- [ ] Machine learning for improved parsing accuracy
- [ ] Signal quality scoring and filtering
- [ ] Integration with more platforms (Discord, Slack)
- [ ] Advanced web scraping with proxies
- [ ] Custom webhook payload formats
- [ ] Channel marketplace (users share channels)

## Technical Constraints

1. **Laravel 8 Framework** - Must work within existing Laravel 8 constraints
2. **PHP 7.3/8.0** - Must be compatible with existing PHP version
3. **MySQL Database** - Use existing database structure
4. **Queue System** - Use Laravel's built-in queue (database or Redis)
5. **Existing Models** - Must extend/use existing Signal, User, Plan models
6. **Admin Panel** - Must integrate with existing admin interface
7. **No Breaking Changes** - Must not break existing signal functionality

## Evolution History

### Version 2.0 (2025-01-28) - Major Architecture Evolution

**Key Changes:**
1. **Two-Tier Architecture**: Separated "Signal Sources" (connections) from "Channel Forwarding" (assignment/forwarding)
2. **Admin Ownership**: Added `is_admin_owned` and `scope` fields to support admin-managed channels
3. **Channel Assignment**: Implemented assignment system (users, plans, global scope)
4. **Telegram MTProto**: Added support for user account authentication (not just bot tokens)
5. **AI Parsing**: Integrated OpenAI and Gemini providers for fallback parsing
6. **Pattern Templates**: Full UI for managing parsing patterns
7. **Analytics**: Signal analytics and reporting system
8. **Signal Distribution**: `DistributeAdminSignalJob` for distributing signals to assigned users/plans

**New Routes:**
- `/admin/signal-sources` - Signal source connection management
- `/admin/channel-forwarding` - Channel forwarding and assignment
- `/user/signal-sources` - User's own signal sources
- `/user/channel-forwarding` - Channels assigned to user

**New Controllers:**
- `SignalSourceController` (Backend & User)
- `ChannelForwardingController` (Backend & User)
- `PatternTemplateController`
- `SignalAnalyticsController`
- `AiConfigurationController`

**New Models:**
- `MessageParsingPattern` - Pattern template management
- `SignalAnalytic` - Analytics tracking
- `AiConfiguration` - AI provider configuration
- `ChannelSourceUser` (pivot) - User assignments
- `ChannelSourcePlan` (pivot) - Plan assignments

**New Services:**
- `ChannelAssignmentService` - Channel assignment logic
- `SignalAnalyticsService` - Analytics calculations
- `ReportService` - Report generation
- `PatternTemplateService` - Pattern management
- `TelegramMtprotoService` - MTProto integration
- `AiProviderFactory` - AI provider abstraction

**New Parsers:**
- `AdvancedPatternParser` - Enhanced regex/template parsing
- `AiMessageParser` - AI-powered parsing fallback

**New Jobs:**
- `DistributeAdminSignalJob` - Distribute signals to assigned users/plans

## Data Model Additions

### New Tables
1. **channel_sources** - Store channel configurations
   - Added: `is_admin_owned` (boolean), `scope` (enum: user/plan/global), `user_id` (nullable)
2. **channel_messages** - Log all incoming messages
3. **message_parsing_patterns** - Pattern template management
4. **signal_analytics** - Analytics tracking per channel/plan
5. **channel_source_users** - Pivot table for user assignments
6. **channel_source_plans** - Pivot table for plan assignments
7. **ai_configurations** - AI provider configuration

### Modified Tables
1. **signals** - Add: channel_source_id, auto_created, message_hash
2. **channel_sources** - Extended with admin ownership and scope fields

See research document for detailed schema definitions.

