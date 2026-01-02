# Tasks: Multi-Channel Signal Addon

**Created:** 2025-01-27
**Last Updated:** 2025-01-28
**Status:** ACTIVE
**Version:** 2.0

## Task Breakdown

### Phase 1: Foundation (Week 1-2)

#### Task 1.1: Create Database Migrations
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** HIGH

**Description:**
Create database migrations for channel_sources, channel_messages tables, and modify signals table. Include all indexes and foreign keys.

**Acceptance Criteria:**
- [ ] Migration for `channel_sources` table created with all columns
- [ ] Migration for `channel_messages` table created with all columns
- [ ] Migration to modify `signals` table (add channel_source_id, auto_created, message_hash)
- [ ] All indexes created (user_id, status, type, message_hash, etc.)
- [ ] Foreign key constraints properly defined
- [ ] Migration can be rolled back successfully
- [ ] Migration tested on development database

**Dependencies:**
- None (first task)

**Technical Notes:**
- Use Laravel migration naming: `YYYY_MM_DD_HHMMSS_create_channel_sources_table.php`
- Follow existing migration patterns in codebase
- Use `encrypted` cast preparation for config JSON field
- Ensure proper charset: utf8mb4_unicode_ci

---

#### Task 1.2: Create ChannelSource Model
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 3 hours
**Priority:** HIGH

**Description:**
Create Eloquent model for ChannelSource with relationships, casts, and accessors/mutators.

**Acceptance Criteria:**
- [ ] ChannelSource model created in `app/Models/ChannelSource.php`
- [ ] Relationships defined: belongsTo(User), belongsTo(Plan, Market, TimeFrame for defaults)
- [ ] Config field cast to encrypted JSON
- [ ] Status enum handling (active, paused, error)
- [ ] Type enum handling (telegram, api, web_scrape, rss)
- [ ] Scopes for filtering (active, byType, byUser)
- [ ] Fillable fields properly defined
- [ ] Model follows existing codebase patterns

**Dependencies:**
- Task 1.1 (Database migrations)

**Technical Notes:**
- Extend existing Model base class
- Use `encrypted` cast for sensitive config data
- Add helper methods: `isActive()`, `pause()`, `resume()`, `incrementError()`

---

#### Task 1.3: Create ChannelMessage Model
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 3 hours
**Priority:** HIGH

**Description:**
Create Eloquent model for ChannelMessage with relationships and helper methods.

**Acceptance Criteria:**
- [ ] ChannelMessage model created in `app/Models/ChannelMessage.php`
- [ ] Relationships defined: belongsTo(ChannelSource), belongsTo(Signal)
- [ ] Parsed_data field cast to array/JSON
- [ ] Status enum handling (pending, processed, failed, duplicate, manual_review)
- [ ] Scopes for filtering (byStatus, byChannelSource, pending, failed)
- [ ] Helper methods: `markAsProcessed()`, `markAsFailed()`, `markAsDuplicate()`
- [ ] Message hash generation method
- [ ] Model follows existing codebase patterns

**Dependencies:**
- Task 1.1 (Database migrations)

**Technical Notes:**
- Use SHA256 for message hashing
- Store raw message as TEXT (can be large)
- Add index on message_hash for duplicate detection

---

#### Task 1.4: Extend Signal Model
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 2 hours
**Priority:** HIGH

**Description:**
Add relationships and helper methods to existing Signal model for channel source integration.

**Acceptance Criteria:**
- [ ] Relationship added: belongsTo(ChannelSource)
- [ ] Scopes added: `autoCreated()`, `byChannelSource()`
- [ ] Helper method: `isAutoCreated()` returns boolean
- [ ] Helper method: `getChannelSource()` returns ChannelSource or null
- [ ] No breaking changes to existing Signal functionality
- [ ] All existing tests still pass

**Dependencies:**
- Task 1.1 (Database migrations)
- Task 1.2 (ChannelSource model)

**Technical Notes:**
- Be careful not to break existing SignalService
- Use nullable relationship (channel_source_id can be NULL)
- Add optional eager loading for channel source

---

#### Task 1.5: Create ChannelAdapterInterface
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 2 hours
**Priority:** HIGH

**Description:**
Create interface that all channel adapters must implement.

**Acceptance Criteria:**
- [ ] Interface created in `app/Contracts/ChannelAdapterInterface.php`
- [ ] Methods defined: `connect()`, `disconnect()`, `fetchMessages()`, `validateConfig()`
- [ ] Return types properly defined
- [ ] Exception types documented
- [ ] Interface follows PSR standards

**Dependencies:**
- None (can be created early)

**Technical Notes:**
- Use strict types: `declare(strict_types=1);`
- Document method contracts with PHPDoc
- Consider using return type hints: `Collection`, `bool`

---

#### Task 1.6: Create Base Channel Adapter Class
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 3 hours
**Priority:** MEDIUM

**Description:**
Create abstract base class with common adapter functionality.

**Acceptance Criteria:**
- [ ] Base class created in `app/Adapters/BaseChannelAdapter.php`
- [ ] Implements ChannelAdapterInterface
- [ ] Common properties: config, channelSource, status
- [ ] Common methods: `getConfig()`, `setConfig()`, `logError()`
- [ ] Error handling helper methods
- [ ] Rate limiting helper methods
- [ ] Abstract methods for channel-specific implementation

**Dependencies:**
- Task 1.5 (ChannelAdapterInterface)

**Technical Notes:**
- Use abstract class for shared functionality
- Implement rate limiting logic in base class
- Add logging functionality

---

#### Task 1.7: Create ProcessChannelMessage Queue Job
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** HIGH

**Description:**
Create Laravel queue job for processing incoming channel messages asynchronously.

**Acceptance Criteria:**
- [ ] Job created in `app/Jobs/ProcessChannelMessage.php`
- [ ] Implements ShouldQueue interface
- [ ] Handles job failures gracefully
- [ ] Logs processing attempts
- [ ] Retries on transient failures (max 3 attempts)
- [ ] Stores message in ChannelMessage table
- [ ] Dispatches to parsing pipeline
- [ ] Handles duplicate detection

**Dependencies:**
- Task 1.3 (ChannelMessage model)
- Task 1.5 (ChannelAdapterInterface)

**Technical Notes:**
- Use Laravel's queue system
- Set appropriate timeout (300 seconds)
- Use `tries` property for retry attempts
- Log job failures for debugging

---

#### Task 1.8: Create Message Parsing Interfaces
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 2 hours
**Priority:** MEDIUM

**Description:**
Create interfaces for message parsers and parsing pipeline.

**Acceptance Criteria:**
- [ ] MessageParserInterface created with `canParse()` and `parse()` methods
- [ ] ParsedSignalData DTO class created
- [ ] ParsingPipeline interface/class structure defined
- [ ] Confidence scoring interface defined
- [ ] All interfaces properly documented

**Dependencies:**
- None (can be created early)

**Technical Notes:**
- ParsedSignalData should be immutable
- Use value objects for parsed data
- Define confidence score range (0-100)

---

### Phase 2: Telegram Integration (Week 3-4)

#### Task 2.1: Create Telegram Adapter
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 8 hours
**Priority:** HIGH

**Description:**
Implement Telegram channel adapter using Telegram Bot API.

**Acceptance Criteria:**
- [ ] TelegramAdapter class created extending BaseChannelAdapter
- [ ] Implements ChannelAdapterInterface
- [ ] Bot token validation method
- [ ] Channel access verification (getChat API)
- [ ] Message fetching via getUpdates (long polling)
- [ ] Webhook support for real-time updates
- [ ] Update ID tracking to avoid duplicates
- [ ] Error handling for API errors (403, 429, etc.)
- [ ] Rate limiting respect (30 messages/second)

**Dependencies:**
- Task 1.6 (BaseChannelAdapter)
- Task 1.5 (ChannelAdapterInterface)

**Technical Notes:**
- Use Guzzle HTTP client for API calls
- Store last_update_id to avoid reprocessing
- Handle Telegram API rate limits (429 errors)
- Support both webhook and long polling modes

---

#### Task 2.2: Create Telegram Webhook Controller
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** HIGH

**Description:**
Create webhook endpoint for receiving Telegram updates.

**Acceptance Criteria:**
- [ ] Controller created in `app/Http/Controllers/Api/TelegramWebhookController.php`
- [ ] Route defined for webhook endpoint
- [ ] Webhook signature verification (optional, Telegram doesn't require it)
- [ ] Receives update payload
- [ ] Dispatches ProcessChannelMessage job
- [ ] Returns 200 OK immediately (async processing)
- [ ] Handles malformed requests gracefully
- [ ] Logs webhook requests

**Dependencies:**
- Task 1.7 (ProcessChannelMessage job)
- Task 2.1 (TelegramAdapter)

**Technical Notes:**
- Route: `POST /api/webhook/telegram/{channel_source_id}`
- Use route model binding for channel_source_id
- Return response quickly (<200ms)
- Process in background via queue

---

#### Task 2.3: Create Telegram Channel Service
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 6 hours
**Priority:** HIGH

**Description:**
Create service class for managing Telegram channel operations.

**Acceptance Criteria:**
- [ ] Service created in `app/Services/TelegramChannelService.php`
- [ ] Methods: `createChannel()`, `validateConnection()`, `testBotToken()`, `updateWebhook()`
- [ ] Channel creation with validation
- [ ] Bot token validation
- [ ] Channel accessibility check
- [ ] Webhook setup/removal
- [ ] Error handling and user-friendly messages
- [ ] Follows existing Service pattern (SignalService)

**Dependencies:**
- Task 1.2 (ChannelSource model)
- Task 2.1 (TelegramAdapter)

**Technical Notes:**
- Follow existing Service class patterns
- Return array with 'type' => 'success'/'error' and 'message'
- Validate bot token before saving
- Test channel access before activating

---

#### Task 2.4: Create Telegram Channel Setup Form (User Interface)
**Status:** TODO
**Assignee:** Frontend Developer
**Estimate:** 6 hours
**Priority:** HIGH

**Description:**
Create user interface for adding and managing Telegram channels.

**Acceptance Criteria:**
- [ ] Form view created in `resources/views/user/channel/telegram/create.blade.php`
- [ ] Fields: Channel name, Bot token, Channel username/ID
- [ ] Form validation (client-side and server-side)
- [ ] Test connection button
- [ ] Success/error message display
- [ ] Responsive design (Bootstrap)
- [ ] Follows existing UI patterns

**Dependencies:**
- Task 2.3 (TelegramChannelService)
- Task 1.2 (ChannelSource model)

**Technical Notes:**
- Use existing form patterns from codebase
- Add AJAX for test connection
- Show loading state during validation
- Display clear instructions for bot setup

---

#### Task 2.5: Create Telegram Channel List View
**Status:** TODO
**Assignee:** Frontend Developer
**Estimate:** 4 hours
**Priority:** HIGH

**Description:**
Create view for listing user's Telegram channels with status.

**Acceptance Criteria:**
- [ ] List view created in `resources/views/user/channel/index.blade.php`
- [ ] Displays: Name, Type, Status, Last Processed, Error Count
- [ ] Filter by status (active, paused, error)
- [ ] Actions: Edit, Pause/Resume, Delete, View Logs
- [ ] Status indicators (green/yellow/red)
- [ ] Pagination support
- [ ] Responsive table design

**Dependencies:**
- Task 1.2 (ChannelSource model)
- Task 2.4 (Create form)

**Technical Notes:**
- Use existing table patterns
- Add status badges
- Implement pause/resume toggle
- Show last processed timestamp

---

#### Task 2.6: Create Telegram Channel Controller (User)
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 6 hours
**Priority:** HIGH

**Description:**
Create controller for user channel management (CRUD operations).

**Acceptance Criteria:**
- [ ] Controller created in `app/Http/Controllers/User/ChannelController.php`
- [ ] Methods: `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `pause()`, `resume()`
- [ ] Form request validation classes
- [ ] Authorization (users can only manage their own channels)
- [ ] Success/error redirects with messages
- [ ] Follows existing controller patterns

**Dependencies:**
- Task 2.3 (TelegramChannelService)
- Task 2.4, 2.5 (Views)

**Technical Notes:**
- Use middleware for authentication
- Check user subscription before allowing channel creation
- Use FormRequest classes for validation
- Follow existing controller structure

---

### Phase 3: Message Parsing System (Week 5-6)

#### Task 3.1: Create Regex Message Parser
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 8 hours
**Priority:** HIGH

**Description:**
Implement regex-based message parser with default patterns.

**Acceptance Criteria:**
- [ ] Parser class created in `app/Parsers/RegexMessageParser.php`
- [ ] Implements MessageParserInterface
- [ ] Default regex patterns for common signal formats
- [ ] Extracts: currency pair, entry price, SL, TP, direction, timeframe
- [ ] Returns ParsedSignalData with confidence score
- [ ] Handles multiple formats (BUY/SELL, LONG/SHORT, etc.)
- [ ] Case-insensitive matching
- [ ] Pattern testing utility method

**Dependencies:**
- Task 1.8 (Message Parsing Interfaces)

**Technical Notes:**
- Store patterns in config file or database
- Use named capture groups for clarity
- Confidence based on number of fields extracted
- Return null if no match

---

#### Task 3.2: Create User-Defined Pattern System
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 10 hours
**Priority:** MEDIUM

**Description:**
Allow users to define custom parsing patterns for their channels.

**Acceptance Criteria:**
- [ ] Migration for message_parsing_patterns table (if not done)
- [ ] MessageParsingPattern model created
- [ ] Pattern storage and retrieval
- [ ] Pattern validation (regex syntax check)
- [ ] Pattern priority system
- [ ] Pattern testing interface (test against sample message)
- [ ] User can create/edit/delete patterns
- [ ] Patterns linked to channel sources or global

**Dependencies:**
- Task 3.1 (RegexMessageParser)
- Task 1.8 (Message Parsing Interfaces)

**Technical Notes:**
- Validate regex syntax before saving
- Allow pattern testing before saving
- Support pattern variables/placeholders
- Store pattern priority for ordering

---

#### Task 3.3: Create Parsing Pipeline
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 6 hours
**Priority:** HIGH

**Description:**
Create pipeline that tries multiple parsers in sequence.

**Acceptance Criteria:**
- [ ] Pipeline class created in `app/Parsers/ParsingPipeline.php`
- [ ] Registers parsers with priority
- [ ] Tries parsers in order until one succeeds
- [ ] Returns first successful parse with highest confidence
- [ ] Falls back to manual review if all parsers fail
- [ ] Logs which parser succeeded
- [ ] Allows adding/removing parsers dynamically

**Dependencies:**
- Task 3.1 (RegexMessageParser)
- Task 3.2 (User-Defined Patterns)
- Task 1.8 (Message Parsing Interfaces)

**Technical Notes:**
- Use strategy pattern for parsers
- Maintain parser registry
- Return confidence score from best parser
- Log parsing attempts for debugging

---

#### Task 3.4: Create ParsedSignalData DTO
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 2 hours
**Priority:** HIGH

**Description:**
Create data transfer object for parsed signal data.

**Acceptance Criteria:**
- [ ] DTO class created in `app/DTOs/ParsedSignalData.php`
- [ ] Properties: currency_pair, open_price, sl, tp, direction, timeframe, market, confidence
- [ ] Validation methods
- [ ] Immutable (value object)
- [ ] Type hints for all properties
- [ ] Helper methods: `isValid()`, `getMissingFields()`

**Dependencies:**
- Task 1.8 (Message Parsing Interfaces)

**Technical Notes:**
- Use readonly properties or final class
- Validate all fields before creating object
- Return null for missing optional fields

---

#### Task 3.5: Integrate Parsing into ProcessChannelMessage Job
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** HIGH

**Description:**
Update ProcessChannelMessage job to use parsing pipeline.

**Acceptance Criteria:**
- [ ] Job calls ParsingPipeline
- [ ] Stores parsed data in ChannelMessage
- [ ] Handles parsing failures (queue for manual review)
- [ ] Stores confidence score
- [ ] Logs parsing results
- [ ] Dispatches to signal creation if parsing succeeds

**Dependencies:**
- Task 1.7 (ProcessChannelMessage job)
- Task 3.3 (Parsing Pipeline)

**Technical Notes:**
- Update job handle() method
- Store parsed_data as JSON in channel_messages
- Set status based on parsing result

---

### Phase 4: Signal Creation Service (Week 7)

#### Task 4.1: Create AutoSignalService
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 8 hours
**Priority:** HIGH

**Description:**
Create service for auto-creating signals from parsed messages.

**Acceptance Criteria:**
- [ ] Service created in `app/Services/AutoSignalService.php`
- [ ] Method: `createFromParsedData(ParsedSignalData, ChannelSource)`
- [ ] Maps parsed data to Signal model
- [ ] Handles missing currency pairs (create or use default)
- [ ] Handles missing timeframes (create or use default)
- [ ] Handles missing markets (create or use default)
- [ ] Creates draft signal (is_published = 0)
- [ ] Links to channel source
- [ ] Stores message hash
- [ ] Assigns to user's default plan or channel default plan

**Dependencies:**
- Task 3.4 (ParsedSignalData DTO)
- Task 1.2 (ChannelSource model)
- Task 1.4 (Signal model)

**Technical Notes:**
- Extend or use existing SignalService
- Follow existing service patterns
- Use database transactions for signal creation
- Handle validation errors gracefully

---

#### Task 4.2: Implement Duplicate Detection
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** HIGH

**Description:**
Prevent duplicate signals from same or similar messages.

**Acceptance Criteria:**
- [ ] Message hash generation (SHA256 of content + timestamp)
- [ ] Check for existing signals with same hash in last 24 hours
- [ ] Check for existing ChannelMessage with same hash
- [ ] Skip duplicate messages (mark as duplicate, don't create signal)
- [ ] Log duplicate detection
- [ ] Configurable time window (default 24 hours)

**Dependencies:**
- Task 1.3 (ChannelMessage model)
- Task 4.1 (AutoSignalService)

**Technical Notes:**
- Use message content + timestamp for hash
- Index message_hash for fast lookups
- Consider fuzzy matching for similar messages (future)

---

#### Task 4.3: Integrate Signal Creation into Message Processing
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 3 hours
**Priority:** HIGH

**Description:**
Connect parsing pipeline output to signal creation.

**Acceptance Criteria:**
- [ ] ProcessChannelMessage job calls AutoSignalService after successful parse
- [ ] Handles signal creation errors
- [ ] Updates ChannelMessage with signal_id
- [ ] Marks message as processed
- [ ] Auto-publishes if confidence >= threshold
- [ ] Queues for manual review if confidence < threshold

**Dependencies:**
- Task 4.1 (AutoSignalService)
- Task 4.2 (Duplicate Detection)
- Task 3.5 (Parsing Integration)

**Technical Notes:**
- Update job handle() method
- Check auto_publish_confidence_threshold from channel source
- Link signal to channel message

---

### Phase 5: API and Web Scraping Adapters (Week 8-9)

#### Task 5.1: Create API Adapter
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 6 hours
**Priority:** MEDIUM

**Description:**
Implement REST API adapter for webhook-based integrations.

**Acceptance Criteria:**
- [ ] ApiAdapter class created extending BaseChannelAdapter
- [ ] Implements ChannelAdapterInterface
- [ ] Webhook endpoint generation per channel source
- [ ] Webhook signature verification (HMAC-SHA256)
- [ ] Payload parsing (JSON)
- [ ] Error handling for invalid payloads
- [ ] Rate limiting support

**Dependencies:**
- Task 1.6 (BaseChannelAdapter)
- Task 1.5 (ChannelAdapterInterface)

**Technical Notes:**
- Generate unique webhook URL per channel source
- Store webhook secret in encrypted config
- Support multiple signature algorithms
- Validate payload structure

---

#### Task 5.2: Create API Webhook Controller
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** MEDIUM

**Description:**
Create webhook endpoint for receiving API messages.

**Acceptance Criteria:**
- [ ] Controller created in `app/Http/Controllers/Api/ApiWebhookController.php`
- [ ] Route: `POST /api/webhook/channel/{channel_source_id}`
- [ ] Signature verification
- [ ] Payload validation
- [ ] Dispatches ProcessChannelMessage job
- [ ] Returns 200 OK quickly
- [ ] Logs webhook requests

**Dependencies:**
- Task 5.1 (ApiAdapter)
- Task 1.7 (ProcessChannelMessage job)

**Technical Notes:**
- Use route model binding
- Verify HMAC signature
- Handle malformed requests
- Rate limit per IP

---

#### Task 5.3: Create Web Scraping Adapter
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 10 hours
**Priority:** MEDIUM

**Description:**
Implement web scraping adapter using Goutte/Guzzle.

**Acceptance Criteria:**
- [ ] WebScrapeAdapter class created extending BaseChannelAdapter
- [ ] Implements ChannelAdapterInterface
- [ ] URL validation and accessibility check
- [ ] CSS selector support
- [ ] XPath support
- [ ] Content extraction
- [ ] Robots.txt checking
- [ ] Rate limiting (configurable delay)
- [ ] Error handling for HTML changes

**Dependencies:**
- Task 1.6 (BaseChannelAdapter)
- Task 1.5 (ChannelAdapterInterface)

**Technical Notes:**
- Use Goutte for scraping (or Symfony DomCrawler)
- Implement exponential backoff on errors
- Cache responses to reduce requests
- Handle JavaScript-rendered content (future: Puppeteer)

---

#### Task 5.4: Create Web Scraping Scheduled Command
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 6 hours
**Priority:** MEDIUM

**Description:**
Create scheduled command to poll web scraping sources.

**Acceptance Criteria:**
- [ ] Command created in `app/Console/Commands/ProcessWebScrapeChannels.php`
- [ ] Fetches all active web_scrape channel sources
- [ ] Calls WebScrapeAdapter for each
- [ ] Detects new content (compare with previous)
- [ ] Creates ChannelMessage for new content
- [ ] Dispatches ProcessChannelMessage job
- [ ] Handles errors gracefully
- [ ] Logs processing results

**Dependencies:**
- Task 5.3 (WebScrapeAdapter)
- Task 1.7 (ProcessChannelMessage job)

**Technical Notes:**
- Schedule via app/Console/Kernel.php
- Run every minute or configurable interval
- Track last processed content hash
- Handle timeout errors

---

#### Task 5.5: Create API and Web Scraping UI Forms
**Status:** TODO
**Assignee:** Frontend Developer
**Estimate:** 8 hours
**Priority:** MEDIUM

**Description:**
Create user interfaces for adding API and web scraping channels.

**Acceptance Criteria:**
- [ ] API channel form with fields: name, webhook URL, secret key
- [ ] Web scraping form with fields: name, URL, CSS selector/XPath
- [ ] Form validation
- [ ] Test connection button for API
- [ ] Test scraping button for web scraping
- [ ] Success/error messages
- [ ] Responsive design

**Dependencies:**
- Task 5.1 (ApiAdapter)
- Task 5.3 (WebScrapeAdapter)
- Task 2.6 (ChannelController)

**Technical Notes:**
- Follow existing form patterns
- Add AJAX for testing
- Show example selectors
- Warn about legal/ethical considerations

---

### Phase 6: RSS Feed Integration (Week 10)

#### Task 6.1: Create RSS Adapter
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 6 hours
**Priority:** LOW

**Description:**
Implement RSS/Atom feed adapter.

**Acceptance Criteria:**
- [ ] RssAdapter class created extending BaseChannelAdapter
- [ ] Implements ChannelAdapterInterface
- [ ] RSS 2.0 format support
- [ ] Atom 1.0 format support
- [ ] Feed URL validation
- [ ] Feed parsing (XML)
- [ ] Item extraction (title, description, link, published date)
- [ ] Error handling for invalid feeds

**Dependencies:**
- Task 1.6 (BaseChannelAdapter)
- Task 1.5 (ChannelAdapterInterface)

**Technical Notes:**
- Use PHP SimpleXML or DOMDocument
- Validate feed format
- Handle feed encoding issues
- Extract published date for filtering

---

#### Task 6.2: Create RSS Scheduled Command
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** LOW

**Description:**
Create scheduled command to poll RSS feeds.

**Acceptance Criteria:**
- [ ] Command created in `app/Console/Commands/ProcessRssChannels.php`
- [ ] Fetches all active rss channel sources
- [ ] Calls RssAdapter for each
- [ ] Detects new feed items (compare published dates/IDs)
- [ ] Creates ChannelMessage for new items
- [ ] Dispatches ProcessChannelMessage job
- [ ] Handles errors gracefully

**Dependencies:**
- Task 6.1 (RssAdapter)
- Task 1.7 (ProcessChannelMessage job)

**Technical Notes:**
- Schedule every 10 minutes (configurable)
- Track last processed item ID/timestamp
- Handle feed errors (403, 404, timeout)

---

#### Task 6.3: Create RSS Feed Setup UI
**Status:** TODO
**Assignee:** Frontend Developer
**Estimate:** 3 hours
**Priority:** LOW

**Description:**
Create user interface for adding RSS feed channels.

**Acceptance Criteria:**
- [ ] RSS feed form with fields: name, feed URL
- [ ] Feed URL validation
- [ ] Test feed button
- [ ] Display feed preview (title, description, items)
- [ ] Success/error messages
- [ ] Responsive design

**Dependencies:**
- Task 6.1 (RssAdapter)
- Task 2.6 (ChannelController)

**Technical Notes:**
- Follow existing form patterns
- Validate feed URL format
- Show feed metadata on test

---

### Phase 7: Admin Review Interface (Week 11-12)

#### Task 7.1: Create ChannelSignalController (Admin)
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 6 hours
**Priority:** HIGH

**Description:**
Create admin controller for reviewing auto-created signals.

**Acceptance Criteria:**
- [ ] Controller created in `app/Http/Controllers/Backend/ChannelSignalController.php`
- [ ] Methods: `index()`, `show()`, `edit()`, `update()`, `approve()`, `reject()`, `bulkApprove()`, `bulkReject()`
- [ ] Filter by channel source, status, date
- [ ] Search functionality
- [ ] Pagination support
- [ ] Authorization (admin only)

**Dependencies:**
- Task 1.4 (Signal model)
- Task 1.3 (ChannelMessage model)

**Technical Notes:**
- Use admin middleware
- Follow existing admin controller patterns
- Use FormRequest for validation

---

#### Task 7.2: Create Admin Signal Review Views
**Status:** TODO
**Assignee:** Frontend Developer
**Estimate:** 10 hours
**Priority:** HIGH

**Description:**
Create admin interface for reviewing and editing auto-created signals.

**Acceptance Criteria:**
- [ ] List view: `resources/views/backend/channel-signal/index.blade.php`
- [ ] Detail view: `resources/views/backend/channel-signal/show.blade.php`
- [ ] Edit view: `resources/views/backend/channel-signal/edit.blade.php`
- [ ] Display: Original message, parsed data, confidence score, channel source
- [ ] Actions: Approve, Reject, Edit, Bulk actions
- [ ] Filter and search UI
- [ ] Responsive design

**Dependencies:**
- Task 7.1 (ChannelSignalController)

**Technical Notes:**
- Follow existing admin view patterns
- Show original message and parsed data side-by-side
- Highlight confidence score
- Add bulk action checkboxes

---

#### Task 7.3: Extend SignalService for Auto-Publish
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** HIGH

**Description:**
Add auto-publish functionality to SignalService.

**Acceptance Criteria:**
- [ ] Method: `approveAndPublish(Signal $signal)`
- [ ] Updates signal: is_published = 1, published_date = now
- [ ] Calls existing `sent()` method for distribution
- [ ] Updates ChannelMessage status
- [ ] Logs approval action
- [ ] Handles errors gracefully

**Dependencies:**
- Existing SignalService
- Task 1.4 (Signal model)

**Technical Notes:**
- Reuse existing signal publishing logic
- Don't duplicate code
- Update channel message status to processed

---

#### Task 7.4: Create Signal Rejection Handler
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 3 hours
**Priority:** MEDIUM

**Description:**
Handle signal rejection with reason tracking.

**Acceptance Criteria:**
- [ ] Method: `rejectSignal(Signal $signal, string $reason)`
- [ ] Stores rejection reason in ChannelMessage
- [ ] Deletes or marks signal as rejected
- [ ] Updates ChannelMessage status
- [ ] Logs rejection action
- [ ] Optional: Notify user of rejection

**Dependencies:**
- Task 1.3 (ChannelMessage model)
- Task 7.1 (ChannelSignalController)

**Technical Notes:**
- Store rejection reason in channel_messages table
- Consider soft delete for signals
- Update channel source error count

---

### Phase 8: Monitoring and Error Handling (Week 13)

#### Task 8.1: Implement Error Tracking
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 6 hours
**Priority:** MEDIUM

**Description:**
Comprehensive error tracking and logging system.

**Acceptance Criteria:**
- [ ] Error logging in all adapters
- [ ] Error count tracking in ChannelSource
- [ ] Last error message storage
- [ ] Error categorization (connection, parsing, API, etc.)
- [ ] Auto-pause after N consecutive errors (configurable)
- [ ] Error notification system

**Dependencies:**
- Task 1.2 (ChannelSource model)
- All adapter implementations

**Technical Notes:**
- Use Laravel logging
- Store errors in channel_sources.last_error
- Increment error_count
- Auto-pause threshold: 10 errors (configurable)

---

#### Task 8.2: Create Status Monitoring Dashboard
**Status:** TODO
**Assignee:** Frontend Developer
**Estimate:** 8 hours
**Priority:** MEDIUM

**Description:**
Create dashboard for monitoring channel health and statistics.

**Acceptance Criteria:**
- [ ] Dashboard view: `resources/views/user/channel/dashboard.blade.php`
- [ ] Displays: Active channels, Error rate, Messages processed today, Last processed timestamp
- [ ] Charts/graphs for statistics
- [ ] Filter by channel, date range
- [ ] Export statistics (optional)
- [ ] Responsive design

**Dependencies:**
- Task 1.2 (ChannelSource model)
- Task 1.3 (ChannelMessage model)

**Technical Notes:**
- Use existing chart library if available
- Show real-time status
- Cache statistics for performance

---

#### Task 8.3: Implement Retry Logic
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** MEDIUM

**Description:**
Implement exponential backoff retry logic for failed jobs.

**Acceptance Criteria:**
- [ ] Retry logic in ProcessChannelMessage job
- [ ] Exponential backoff (1s, 2s, 4s, 8s)
- [ ] Max retry attempts (3)
- [ ] Permanent failure handling
- [ ] Transient vs permanent error detection
- [ ] Update ChannelMessage status on failure

**Dependencies:**
- Task 1.7 (ProcessChannelMessage job)

**Technical Notes:**
- Use Laravel's retry mechanism
- Distinguish transient (network) vs permanent (invalid format) errors
- Don't retry permanent failures

---

#### Task 8.4: Create Notification System
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 4 hours
**Priority:** LOW

**Description:**
Notify users and admins of important events.

**Acceptance Criteria:**
- [ ] Notify user when channel connection fails
- [ ] Notify user when channel auto-paused
- [ ] Notify admin when new auto-created signals need review
- [ ] Email notifications
- [ ] In-app notifications (optional)
- [ ] Notification preferences (configurable)

**Dependencies:**
- Task 8.1 (Error Tracking)

**Technical Notes:**
- Use Laravel notifications
- Queue notifications
- Allow users to configure notification preferences

---

### Phase 9: Testing and Refinement (Week 14-15)

#### Task 9.1: Write Unit Tests
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 16 hours
**Priority:** HIGH

**Description:**
Create comprehensive unit tests for core functionality.

**Acceptance Criteria:**
- [ ] Tests for all adapters
- [ ] Tests for parsers
- [ ] Tests for services
- [ ] Tests for models
- [ ] Test coverage >= 70%
- [ ] All tests pass
- [ ] Tests use factories and mocks

**Dependencies:**
- All implementation tasks

**Technical Notes:**
- Use PHPUnit
- Create model factories
- Mock external API calls
- Test error scenarios

---

#### Task 9.2: Write Integration Tests
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 12 hours
**Priority:** HIGH

**Description:**
Create integration tests for end-to-end workflows.

**Acceptance Criteria:**
- [ ] Test: Message received → Parsed → Signal created
- [ ] Test: Webhook → Queue → Processing
- [ ] Test: Admin approval → Signal published
- [ ] Test: Duplicate detection
- [ ] Test: Error handling
- [ ] All integration tests pass

**Dependencies:**
- All implementation tasks

**Technical Notes:**
- Use Laravel's testing features
- Test database transactions
- Test queue processing
- Use refresh database trait

---

#### Task 9.3: Performance Testing
**Status:** TODO
**Assignee:** Backend Developer
**Estimate:** 8 hours
**Priority:** MEDIUM

**Description:**
Test system performance under load.

**Acceptance Criteria:**
- [ ] Load test: 1000 messages/minute
- [ ] Database query optimization
- [ ] Queue processing speed
- [ ] Memory usage monitoring
- [ ] Identify bottlenecks
- [ ] Performance report created

**Dependencies:**
- All implementation tasks

**Technical Notes:**
- Use Laravel Telescope or similar
- Profile slow queries
- Optimize database indexes
- Test with multiple queue workers

---

#### Task 9.4: Bug Fixes and Refinements
**Status:** TODO
**Assignee:** All Developers
**Estimate:** 16 hours
**Priority:** HIGH

**Description:**
Fix identified bugs and refine implementation.

**Acceptance Criteria:**
- [ ] All critical bugs fixed
- [ ] Code review completed
- [ ] Code follows Laravel best practices
- [ ] Documentation updated
- [ ] Performance optimized

**Dependencies:**
- Task 9.1, 9.2, 9.3 (Testing)

**Technical Notes:**
- Review all code
- Fix linting errors
- Optimize database queries
- Update comments and documentation

---

### Phase 10: Documentation and Deployment (Week 16)

#### Task 10.1: Create User Documentation
**Status:** TODO
**Assignee:** Technical Writer / Developer
**Estimate:** 8 hours
**Priority:** MEDIUM

**Description:**
Create user guide for channel setup and management.

**Acceptance Criteria:**
- [ ] Channel setup guide (Telegram, API, Web Scrape, RSS)
- [ ] Troubleshooting guide
- [ ] FAQ section
- [ ] Screenshots and examples
- [ ] Video tutorial (optional)

**Dependencies:**
- All implementation complete

**Technical Notes:**
- Create markdown documentation
- Include step-by-step instructions
- Add troubleshooting common issues

---

#### Task 10.2: Create Developer Documentation
**Status:** TODO
**Assignee:** Developer
**Estimate:** 6 hours
**Priority:** LOW

**Description:**
Create technical documentation for developers.

**Acceptance Criteria:**
- [ ] Architecture documentation
- [ ] API documentation
- [ ] Extension guide (adding new adapters)
- [ ] Code comments and PHPDoc

**Dependencies:**
- All implementation complete

**Technical Notes:**
- Document adapter pattern
- Explain parsing pipeline
- Provide extension examples

---

#### Task 10.3: Deployment Preparation
**Status:** TODO
**Assignee:** DevOps / Developer
**Estimate:** 4 hours
**Priority:** HIGH

**Description:**
Prepare production environment and deployment.

**Acceptance Criteria:**
- [ ] Environment variables documented
- [ ] Queue worker configuration
- [ ] Scheduled commands configured
- [ ] Webhook URLs configured
- [ ] Database backup plan
- [ ] Rollback plan documented

**Dependencies:**
- All implementation complete

**Technical Notes:**
- Create deployment checklist
- Configure supervisor for queue workers
- Set up monitoring
- Test deployment on staging

---

#### Task 10.4: Production Deployment
**Status:** TODO
**Assignee:** DevOps / Developer
**Estimate:** 4 hours
**Priority:** HIGH

**Description:**
Deploy to production environment.

**Acceptance Criteria:**
- [ ] Database migrations run successfully
- [ ] Code deployed to production
- [ ] Queue workers started
- [ ] Scheduled commands active
- [ ] Webhooks configured
- [ ] Monitoring active
- [ ] Smoke tests passed

**Dependencies:**
- Task 10.3 (Deployment Preparation)

**Technical Notes:**
- Deploy during low-traffic period
- Monitor closely after deployment
- Have rollback plan ready
- Test all channel types after deployment

---

## Dependencies Graph

```
Phase 1 (Foundation)
├── 1.1 Database Migrations
├── 1.2 ChannelSource Model (depends on: 1.1)
├── 1.3 ChannelMessage Model (depends on: 1.1)
├── 1.4 Signal Model Extension (depends on: 1.1, 1.2)
├── 1.5 ChannelAdapterInterface
├── 1.6 BaseChannelAdapter (depends on: 1.5)
├── 1.7 ProcessChannelMessage Job (depends on: 1.3, 1.5)
└── 1.8 Message Parsing Interfaces

Phase 2 (Telegram)
├── 2.1 TelegramAdapter (depends on: 1.6, 1.5)
├── 2.2 TelegramWebhookController (depends on: 1.7, 2.1)
├── 2.3 TelegramChannelService (depends on: 1.2, 2.1)
├── 2.4 Telegram Setup UI (depends on: 2.3, 1.2)
├── 2.5 Channel List View (depends on: 1.2, 2.4)
└── 2.6 ChannelController (depends on: 2.3, 2.4, 2.5)

Phase 3 (Parsing)
├── 3.1 RegexParser (depends on: 1.8)
├── 3.2 User-Defined Patterns (depends on: 3.1, 1.8)
├── 3.3 ParsingPipeline (depends on: 3.1, 3.2, 1.8)
├── 3.4 ParsedSignalData DTO (depends on: 1.8)
└── 3.5 Integrate Parsing (depends on: 1.7, 3.3)

Phase 4 (Signal Creation)
├── 4.1 AutoSignalService (depends on: 3.4, 1.2, 1.4)
├── 4.2 Duplicate Detection (depends on: 1.3, 4.1)
└── 4.3 Integrate Creation (depends on: 4.1, 4.2, 3.5)

Phase 5 (API & Web Scrape)
├── 5.1 ApiAdapter (depends on: 1.6, 1.5)
├── 5.2 ApiWebhookController (depends on: 5.1, 1.7)
├── 5.3 WebScrapeAdapter (depends on: 1.6, 1.5)
├── 5.4 WebScrapeCommand (depends on: 5.3, 1.7)
└── 5.5 API/Scrape UI (depends on: 5.1, 5.3, 2.6)

Phase 6 (RSS)
├── 6.1 RssAdapter (depends on: 1.6, 1.5)
├── 6.2 RssCommand (depends on: 6.1, 1.7)
└── 6.3 RSS UI (depends on: 6.1, 2.6)

Phase 7 (Admin Review)
├── 7.1 ChannelSignalController (depends on: 1.4, 1.3)
├── 7.2 Admin Views (depends on: 7.1)
├── 7.3 Auto-Publish (depends on: 1.4)
└── 7.4 Rejection Handler (depends on: 1.3, 7.1)

Phase 8 (Monitoring)
├── 8.1 Error Tracking (depends on: 1.2, all adapters)
├── 8.2 Status Dashboard (depends on: 1.2, 1.3)
├── 8.3 Retry Logic (depends on: 1.7)
└── 8.4 Notifications (depends on: 8.1)

Phase 9 (Testing)
├── 9.1 Unit Tests (depends on: all implementation)
├── 9.2 Integration Tests (depends on: all implementation)
├── 9.3 Performance Tests (depends on: all implementation)
└── 9.4 Bug Fixes (depends on: 9.1, 9.2, 9.3)

Phase 10 (Deployment)
├── 10.1 User Docs (depends on: all implementation)
├── 10.2 Developer Docs (depends on: all implementation)
├── 10.3 Deployment Prep (depends on: all implementation)
└── 10.4 Production Deploy (depends on: 10.3)
```

## Progress Tracking

- **Total Tasks:** 58 (Original) + 15 (New features) = 73
- **Completed:** ~65
- **In Progress:** 0
- **Blocked:** 0
- **Remaining:** ~8 (mostly testing and documentation)

**Note:** Implementation has evolved significantly beyond original tasks. See EVOLUTION.md for details.

## Estimates

- **Total Estimated Time:** 232 hours (~29 working days / 6 weeks for 1 developer)
- **Actual Time Spent:** TBD
- **Variance:** TBD

**Breakdown by Phase:**
- Phase 1 (Foundation): 24 hours
- Phase 2 (Telegram): 34 hours
- Phase 3 (Parsing): 30 hours
- Phase 4 (Signal Creation): 15 hours
- Phase 5 (API & Web Scrape): 34 hours
- Phase 6 (RSS): 13 hours
- Phase 7 (Admin Review): 23 hours
- Phase 8 (Monitoring): 22 hours
- Phase 9 (Testing): 52 hours
- Phase 10 (Deployment): 22 hours

## Notes

### Development Order
Tasks should be completed in phase order, but some tasks within phases can be parallelized:
- Frontend tasks (views) can be done in parallel with backend tasks
- Testing can be done incrementally as features are completed
- Documentation can be written alongside development

### Critical Path
The critical path for MVP (Minimum Viable Product) is:
1. Phase 1 (Foundation) - Required for everything
2. Phase 2 (Telegram) - Primary channel type
3. Phase 3 (Parsing) - Required for signal creation
4. Phase 4 (Signal Creation) - Core functionality
5. Phase 7 (Admin Review) - Required for publishing signals

Phases 5, 6, 8 can be done in parallel or after MVP is complete.

### Risk Mitigation
- Start with Phase 1 tasks immediately (foundation)
- Test Telegram integration early (most critical)
- Implement parsing with simple regex first, add complexity later
- Deploy to staging environment early for testing

### Future Enhancements
- Task: Add Machine Learning parsing (future)
- Task: Add Discord/Slack adapters (future)
- Task: Add signal quality scoring (future)
- Task: Add channel analytics dashboard (✅ COMPLETED - SignalAnalytics implemented)

### Completed Beyond Original Scope
- ✅ Telegram MTProto integration
- ✅ AI-powered parsing (OpenAI, Gemini)
- ✅ Pattern template management UI
- ✅ Signal analytics and reporting
- ✅ Channel assignment system (users/plans)
- ✅ Two-tier architecture (Signal Sources + Channel Forwarding)
- ✅ Admin ownership model
- ✅ Signal distribution job

