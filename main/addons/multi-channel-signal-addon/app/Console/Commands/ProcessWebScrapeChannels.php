<?php

namespace Addons\MultiChannelSignalAddon\App\Console\Commands;

use Addons\MultiChannelSignalAddon\App\Adapters\WebScrapeAdapter;
use Addons\MultiChannelSignalAddon\App\Jobs\ProcessChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelMessage;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessWebScrapeChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:process-web-scrape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process web scraping channels and fetch new content';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Processing web scraping channels...');

        $channels = ChannelSource::where('type', 'web_scrape')
            ->where('status', 'active')
            ->get();

        if ($channels->isEmpty()) {
            $this->info('No active web scraping channels found.');
            return 0;
        }

        $processed = 0;
        $errors = 0;

        foreach ($channels as $channel) {
            try {
                $adapter = new WebScrapeAdapter($channel);
                
                if (!$adapter->connect($channel)) {
                    $this->error("Failed to connect to channel: {$channel->name}");
                    $errors++;
                    continue;
                }

                $messages = $adapter->fetchMessages();

                foreach ($messages as $messageData) {
                    // Generate message hash
                    $messageHash = ChannelMessage::generateHash($messageData['text'], $messageData['timestamp']);

                    // Check for duplicate
                    $existingMessage = ChannelMessage::where('message_hash', $messageHash)
                        ->where('channel_source_id', $channel->id)
                        ->where('created_at', '>=', now()->subDay())
                        ->first();

                    if ($existingMessage) {
                        continue;
                    }

                    // Create channel message
                    $channelMessage = ChannelMessage::create([
                        'channel_source_id' => $channel->id,
                        'raw_message' => $messageData['text'],
                        'message_hash' => $messageHash,
                        'status' => 'pending',
                    ]);

                    // Dispatch job to process message
                    ProcessChannelMessage::dispatch($channelMessage);

                    $processed++;
                }

                // Update last processed
                $channel->updateLastProcessed();

                $this->info("Processed channel: {$channel->name} ({$messages->count()} new messages)");

            } catch (\Exception $e) {
                $this->error("Error processing channel {$channel->name}: " . $e->getMessage());
                Log::error("Web scraping error for channel {$channel->id}: " . $e->getMessage());
                $channel->incrementError($e->getMessage());
                $errors++;
            }

            // Rate limiting - delay between channels
            sleep(2);
        }

        $this->info("Processing complete. Processed: {$processed} messages, Errors: {$errors}");

        return 0;
    }
}

