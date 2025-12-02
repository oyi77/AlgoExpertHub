#!/bin/bash
# Script to migrate files to addon structure with namespace updates

cd /home/u875299794/domains/algoexperthub.com/public_html/main

# Copy files and update namespaces
echo "Copying and updating files..."

# Copy adapters
cp app/Adapters/TelegramAdapter.php addons/multi-channel-signal-addon/app/Adapters/
cp app/Adapters/ApiAdapter.php addons/multi-channel-signal-addon/app/Adapters/
cp app/Adapters/WebScrapeAdapter.php addons/multi-channel-signal-addon/app/Adapters/
cp app/Adapters/RssAdapter.php addons/multi-channel-signal-addon/app/Adapters/

# Copy contracts
cp app/Contracts/ChannelAdapterInterface.php addons/multi-channel-signal-addon/app/Contracts/
cp app/Contracts/MessageParserInterface.php addons/multi-channel-signal-addon/app/Contracts/

# Copy DTOs
cp app/DTOs/ParsedSignalData.php addons/multi-channel-signal-addon/app/DTOs/

# Copy parsers
cp app/Parsers/RegexMessageParser.php addons/multi-channel-signal-addon/app/Parsers/
cp app/Parsers/ParsingPipeline.php addons/multi-channel-signal-addon/app/Parsers/

# Copy services
cp app/Services/TelegramChannelService.php addons/multi-channel-signal-addon/app/Services/
cp app/Services/AutoSignalService.php addons/multi-channel-signal-addon/app/Services/

# Copy jobs
cp app/Jobs/ProcessChannelMessage.php addons/multi-channel-signal-addon/app/Jobs/

# Copy commands
cp app/Console/Commands/ProcessRssChannels.php addons/multi-channel-signal-addon/app/Console/Commands/
cp app/Console/Commands/ProcessWebScrapeChannels.php addons/multi-channel-signal-addon/app/Console/Commands/

# Copy controllers
cp app/Http/Controllers/Api/TelegramWebhookController.php addons/multi-channel-signal-addon/app/Http/Controllers/Api/
cp app/Http/Controllers/Api/ApiWebhookController.php addons/multi-channel-signal-addon/app/Http/Controllers/Api/
cp app/Http/Controllers/Backend/ChannelSignalController.php addons/multi-channel-signal-addon/app/Http/Controllers/Backend/
cp app/Http/Controllers/User/ChannelController.php addons/multi-channel-signal-addon/app/Http/Controllers/User/

# Copy migrations
cp database/migrations/2025_01_27_* addons/multi-channel-signal-addon/database/migrations/

echo "Files copied. Now update namespaces in each file from 'App\\' to 'Addons\\MultiChannelSignalAddon\\App\\'"


