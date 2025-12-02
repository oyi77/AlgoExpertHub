<?php

namespace App\Adapters;

use App\Models\ChannelSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RssAdapter extends BaseChannelAdapter
{
    /**
     * Connect to RSS feed.
     *
     * @param ChannelSource $channelSource
     * @return bool
     */
    public function connect(ChannelSource $channelSource): bool
    {
        try {
            $this->channelSource = $channelSource;
            $this->config = $channelSource->config ?? [];

            // Validate feed URL
            $feedUrl = $this->getConfig('feed_url');
            if (empty($feedUrl) || !filter_var($feedUrl, FILTER_VALIDATE_URL)) {
                $this->logError("Invalid feed URL configured");
                return false;
            }

            // Validate feed format
            if (!$this->validateFeedFormat()) {
                return false;
            }

            $this->connected = true;
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to connect to RSS feed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch messages from RSS feed.
     *
     * @return Collection
     */
    public function fetchMessages(): Collection
    {
        if (!$this->connected) {
            $this->connect($this->channelSource);
        }

        $messages = collect();

        try {
            $feedUrl = $this->getConfig('feed_url');
            $lastProcessedId = $this->getConfig('last_processed_item_id');
            $lastProcessedDate = $this->getConfig('last_processed_date');

            // Fetch feed
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ])
                ->get($feedUrl);

            if (!$response->successful()) {
                $this->logError("Failed to fetch RSS feed: HTTP {$response->status()}");
                return $messages;
            }

            $xml = $response->body();

            // Parse RSS/Atom feed
            $feed = $this->parseFeed($xml);

            if (!$feed) {
                return $messages;
            }

            // Process items
            foreach ($feed['items'] as $item) {
                // Skip if already processed
                if ($lastProcessedId && $item['id'] === $lastProcessedId) {
                    continue;
                }

                // Skip if published before last processed date
                if ($lastProcessedDate && isset($item['published'])) {
                    $itemDate = strtotime($item['published']);
                    $lastDate = strtotime($lastProcessedDate);
                    if ($itemDate <= $lastDate) {
                        continue;
                    }
                }

                // Create message from feed item
                $messageText = $item['title'] . "\n\n" . ($item['description'] ?? '');
                if (!empty($item['link'])) {
                    $messageText .= "\n\n" . $item['link'];
                }

                $messages->push([
                    'text' => $messageText,
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'link' => $item['link'] ?? null,
                    'published' => $item['published'] ?? null,
                    'item_id' => $item['id'],
                    'timestamp' => isset($item['published']) ? strtotime($item['published']) : now()->timestamp,
                ]);

                // Update last processed
                $config = $this->channelSource->config;
                $config['last_processed_item_id'] = $item['id'];
                if (isset($item['published'])) {
                    $config['last_processed_date'] = $item['published'];
                }
                $this->channelSource->update(['config' => $config]);
            }

        } catch (\Exception $e) {
            $this->logError("Failed to fetch RSS feed: " . $e->getMessage());
        }

        return $messages;
    }

    /**
     * Validate channel configuration.
     *
     * @param array $config
     * @return bool
     */
    public function validateConfig(array $config): bool
    {
        if (empty($config['feed_url']) || !filter_var($config['feed_url'], FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    /**
     * Get the adapter type.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'rss';
    }

    /**
     * Validate feed format.
     *
     * @return bool
     */
    protected function validateFeedFormat(): bool
    {
        try {
            $feedUrl = $this->getConfig('feed_url');
            $response = Http::timeout(10)->get($feedUrl);

            if (!$response->successful()) {
                return false;
            }

            $xml = $response->body();
            
            // Check if it's valid XML
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            $errors = libxml_get_errors();
            libxml_clear_errors();

            if (!empty($errors)) {
                return false;
            }

            // Check if it's RSS or Atom
            $isRss = $dom->getElementsByTagName('rss')->length > 0;
            $isAtom = $dom->getElementsByTagName('feed')->length > 0;

            return $isRss || $isAtom;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Parse RSS or Atom feed.
     *
     * @param string $xml
     * @return array|null
     */
    protected function parseFeed(string $xml): ?array
    {
        try {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            
            $feed = [
                'title' => null,
                'description' => null,
                'items' => []
            ];

            // Check if RSS
            $rssItems = $dom->getElementsByTagName('item');
            if ($rssItems->length > 0) {
                // RSS 2.0 format
                $channel = $dom->getElementsByTagName('channel')->item(0);
                if ($channel) {
                    $feed['title'] = $this->getNodeValue($channel, 'title');
                    $feed['description'] = $this->getNodeValue($channel, 'description');
                }

                foreach ($rssItems as $item) {
                    $feed['items'][] = [
                        'id' => $this->getNodeValue($item, 'guid') ?: $this->getNodeValue($item, 'link'),
                        'title' => $this->getNodeValue($item, 'title'),
                        'description' => $this->getNodeValue($item, 'description'),
                        'link' => $this->getNodeValue($item, 'link'),
                        'published' => $this->getNodeValue($item, 'pubDate'),
                    ];
                }
            } else {
                // Atom format
                $feedElement = $dom->getElementsByTagName('feed')->item(0);
                if ($feedElement) {
                    $feed['title'] = $this->getNodeValue($feedElement, 'title');
                }

                $entries = $dom->getElementsByTagName('entry');
                foreach ($entries as $entry) {
                    $id = $this->getNodeValue($entry, 'id');
                    $linkElements = $entry->getElementsByTagName('link');
                    $link = null;
                    if ($linkElements->length > 0) {
                        $link = $linkElements->item(0)->getAttribute('href');
                    }

                    $feed['items'][] = [
                        'id' => $id,
                        'title' => $this->getNodeValue($entry, 'title'),
                        'description' => $this->getNodeValue($entry, 'content') ?: $this->getNodeValue($entry, 'summary'),
                        'link' => $link,
                        'published' => $this->getNodeValue($entry, 'published') ?: $this->getNodeValue($entry, 'updated'),
                    ];
                }
            }

            return $feed;
        } catch (\Exception $e) {
            Log::error("RSS feed parsing failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get node value by tag name.
     *
     * @param \DOMElement $parent
     * @param string $tagName
     * @return string|null
     */
    protected function getNodeValue(\DOMElement $parent, string $tagName): ?string
    {
        $nodes = $parent->getElementsByTagName($tagName);
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        return null;
    }
}

