<?php

namespace Addons\MultiChannelSignalAddon\App\Adapters\Traits;

use Illuminate\Support\Collection;
use function Amp\Future\await;
use Revolt\EventLoop;

trait HandlesMessages
{
    /**
     * Fetch messages from Telegram channel/group.
     *
     * @return Collection
     */
    public function fetchMessages(): Collection
    {
        if (!$this->connected && !$this->connect($this->channelSource)) {
            return collect();
        }

        $messages = collect();

        try {
            $channelUsername = $this->getConfig('channel_username');
            $channelId = $this->getConfig('channel_id');

            if (empty($channelUsername) && empty($channelId)) {
                $this->logError("Channel username or ID is required");
                return $messages;
            }

            // Resolve channel peer
            $peer = $channelUsername ?? $channelId;
            
            // Get channel entity
            // According to docs: getInfo() resolves peer to full info
            try {
                if (!EventLoop::getDriver()) {
                    EventLoop::run(function () use ($peer, &$channel) {
                        $infoResult = $this->madeline->getInfo($peer);
                        if ($infoResult instanceof \Amp\Future) {
                            $channel = await([$infoResult])[0];
                        } else {
                            $channel = $infoResult;
                        }
                    });
                } else {
                    $infoResult = $this->madeline->getInfo($peer);
                    if ($infoResult instanceof \Amp\Future) {
                        $channel = await([$infoResult])[0];
                    } else {
                        $channel = $infoResult;
                    }
                }
                $channelId = $channel['bot_api_id'] ?? $channel['id'] ?? null;

                if (!$channelId) {
                    $this->logError("Could not resolve channel: " . $peer);
                    return $messages;
                }
            } catch (\Exception $e) {
                $this->logError("Failed to get channel info for {$peer}: " . $e->getMessage());
                return $messages;
            }

            // Get last processed message ID
            $lastMessageId = $this->getConfig('last_message_id', 0);

            // Get messages from channel
            // According to docs: getHistory() parameters
            // offset_id: ID of message to start from (0 for latest)
            // min_id: Only return messages with ID >= min_id
            // offset_date: Only return messages sent before this date
            $params = [
                'peer' => $peer,
                'limit' => 100,
            ];

            // If we have a last message ID, fetch messages after it
            if ($lastMessageId > 0) {
                $params['min_id'] = $lastMessageId;
                $params['offset_id'] = 0; // Start from latest
            } else {
                // First fetch, get latest messages
                $params['offset_id'] = 0;
            }

            if (!EventLoop::getDriver()) {
                EventLoop::run(function () use ($params, &$result) {
                    $historyResult = $this->madeline->messages->getHistory($params);
                    if ($historyResult instanceof \Amp\Future) {
                        $result = await([$historyResult])[0];
                    } else {
                        $result = $historyResult;
                    }
                });
            } else {
                $historyResult = $this->madeline->messages->getHistory($params);
                if ($historyResult instanceof \Amp\Future) {
                    $result = await([$historyResult])[0];
                } else {
                    $result = $historyResult;
                }
            }

            if (isset($result['messages']) && is_array($result['messages'])) {
                foreach ($result['messages'] as $message) {
                    // Skip if already processed
                    if ($message['id'] <= $lastMessageId) {
                        continue;
                    }

                    // Extract message text
                    $messageText = $message['message'] ?? null;

                    if ($messageText) {
                        $messages->push([
                            'text' => $messageText,
                            'message_id' => $message['id'],
                            'date' => $message['date'] ?? now()->timestamp,
                            'from_id' => $message['from_id'] ?? null,
                            'peer_id' => $channelId,
                        ]);

                        // Update last message ID
                        $lastMessageId = max($lastMessageId, $message['id']);
                    }
                }
            }

            // Save last message ID
            if ($lastMessageId > 0) {
                $config = $this->channelSource->config;
                $config['last_message_id'] = $lastMessageId;
                $this->channelSource->update(['config' => $config]);
            }

        } catch (\Exception $e) {
            $this->logError("Failed to fetch MTProto messages: " . $e->getMessage());
        }

        return $messages;
    }

    /**
     * Fetch sample messages from a specific channel (for preview/parser creation).
     * This method fetches the latest N messages without filtering by last_message_id.
     *
     * @param string|int $channelId Channel ID or username
     * @param int $limit Number of messages to fetch (default: 20)
     * @return array Array with 'success' flag and 'messages' or 'error'
     */
    public function fetchSampleMessages($channelId, int $limit = 20): array
    {
        if (!$this->connected && !$this->connect($this->channelSource)) {
            return [
                'success' => false,
                'error' => 'Failed to connect to Telegram',
            ];
        }

        try {
            // Resolve channel peer
            $peer = $channelId;
            
            // Get channel entity
            try {
                if (!EventLoop::getDriver()) {
                    EventLoop::run(function () use ($peer, &$channel) {
                        $infoResult = $this->madeline->getInfo($peer);
                        if ($infoResult instanceof \Amp\Future) {
                            $channel = await([$infoResult])[0];
                        } else {
                            $channel = $infoResult;
                        }
                    });
                } else {
                    $infoResult = $this->madeline->getInfo($peer);
                    if ($infoResult instanceof \Amp\Future) {
                        $channel = await([$infoResult])[0];
                    } else {
                        $channel = $infoResult;
                    }
                }
            } catch (\Exception $e) {
                $this->logError("Failed to get channel info for {$peer}: " . $e->getMessage());
                return [
                    'success' => false,
                    'error' => 'Failed to resolve channel: ' . $e->getMessage(),
                ];
            }

            // Get latest messages (sample, not filtered by last_message_id)
            $params = [
                'peer' => $peer,
                'limit' => min($limit, 100), // Max 100 per request
                'offset_id' => 0, // Start from latest
            ];

            if (!EventLoop::getDriver()) {
                EventLoop::run(function () use ($params, &$result) {
                    $historyResult = $this->madeline->messages->getHistory($params);
                    if ($historyResult instanceof \Amp\Future) {
                        $result = await([$historyResult])[0];
                    } else {
                        $result = $historyResult;
                    }
                });
            } else {
                $historyResult = $this->madeline->messages->getHistory($params);
                if ($historyResult instanceof \Amp\Future) {
                    $result = await([$historyResult])[0];
                } else {
                    $result = $historyResult;
                }
            }

            $messages = [];
            if (isset($result['messages']) && is_array($result['messages'])) {
                foreach ($result['messages'] as $message) {
                    // Extract message text
                    $messageText = $message['message'] ?? null;

                    if ($messageText) {
                        $messages[] = [
                            'text' => $messageText,
                            'message_id' => $message['id'],
                            'date' => $message['date'] ?? now()->timestamp,
                            'from_id' => $message['from_id'] ?? null,
                            'formatted_date' => isset($message['date']) 
                                ? date('Y-m-d H:i:s', $message['date']) 
                                : now()->format('Y-m-d H:i:s'),
                        ];
                    }
                }
            }

            return [
                'success' => true,
                'messages' => $messages,
                'count' => count($messages),
            ];

        } catch (\Exception $e) {
            $this->logError("Failed to fetch sample messages: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to fetch messages: ' . $e->getMessage(),
            ];
        }
    }
}

