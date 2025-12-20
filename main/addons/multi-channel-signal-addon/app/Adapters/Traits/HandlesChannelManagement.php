<?php

namespace Addons\MultiChannelSignalAddon\App\Adapters\Traits;

use Illuminate\Support\Facades\Log;
use function Amp\Future\await;
use Revolt\EventLoop;

trait HandlesChannelManagement
{
    public function getDialogs(): array
    {
        try {
            if (!$this->connected && !$this->connect($this->channelSource)) {
                return [];
            }

            // According to docs: getFullDialogs() returns full dialog info
            // getDialogs() might also work but getFullDialogs() is preferred
            try {
                if (!EventLoop::getDriver()) {
                    EventLoop::run(function () use (&$dialogs) {
                        $dialogsResult = $this->madeline->getFullDialogs();
                        if ($dialogsResult instanceof \Amp\Future) {
                            $dialogs = await([$dialogsResult])[0];
                        } else {
                            $dialogs = $dialogsResult;
                        }
                    });
                } else {
                    $dialogsResult = $this->madeline->getFullDialogs();
                    if ($dialogsResult instanceof \Amp\Future) {
                        $dialogs = await([$dialogsResult])[0];
                    } else {
                        $dialogs = $dialogsResult;
                    }
                }
            } catch (\Exception $e) {
                Log::error("getFullDialogs() failed: " . $e->getMessage());
                throw new \Exception("Failed to fetch dialogs: " . $e->getMessage());
            }

            // Log dialogs structure for debugging
            $firstDialog = null;
            $firstDialogKeys = [];
            if (is_array($dialogs) && !empty($dialogs)) {
                // Handle both indexed and associative arrays
                $firstDialog = is_array($dialogs) ? (array_values($dialogs)[0] ?? null) : null;
                if (is_array($firstDialog)) {
                    $firstDialogKeys = array_keys($firstDialog);
                }
            }
            
            Log::info("getDialogs() - Raw dialogs structure", [
                'channel_id' => $this->channelSource->id,
                'dialogs_type' => gettype($dialogs),
                'is_array' => is_array($dialogs),
                'count' => is_array($dialogs) ? count($dialogs) : 0,
                'is_associative' => is_array($dialogs) && !empty($dialogs) && array_keys($dialogs) !== range(0, count($dialogs) - 1),
                'first_dialog_keys' => $firstDialogKeys,
                'first_dialog_sample' => $firstDialog ? json_encode(array_slice($firstDialog, 0, 20, true)) : null,
                'first_3_dialogs_summary' => is_array($dialogs) && count($dialogs) >= 1 ? array_map(function($d, $idx) {
                    if (!is_array($d)) return ['index' => $idx, 'type' => gettype($d)];
                    return [
                        'index' => $idx,
                        'keys' => array_keys($d),
                        'has_peer' => isset($d['peer']),
                        'has_entity' => isset($d['entity']),
                        'peer_type' => isset($d['peer']) && is_array($d['peer']) ? ($d['peer']['_'] ?? 'unknown') : 'not_array',
                        'peer_keys' => isset($d['peer']) && is_array($d['peer']) ? array_keys($d['peer']) : [],
                    ];
                }, array_slice(array_values($dialogs), 0, 3), array_keys(array_slice(array_values($dialogs), 0, 3))) : [],
            ]);

            $result = [];
            
            // Handle different return structures from MadelineProto
            // getFullDialogs() returns an associative array where keys are dialog IDs
            // Each dialog has a 'peer' field that needs to be resolved
            if (!is_array($dialogs) || empty($dialogs)) {
                Log::warning("getDialogs() - Empty or invalid dialogs", [
                    'channel_id' => $this->channelSource->id,
                ]);
                return [];
            }
            
            // Convert associative array to indexed array if needed
            // getFullDialogs() returns [dialogId => dialog, ...]
            $dialogsArray = array_values($dialogs);
            
            // Collect all peers first
            $peersToResolve = [];
            $dialogPeers = [];
            foreach ($dialogsArray as $index => $dialog) {
                if (!is_array($dialog)) {
                    continue;
                }
                $peer = $dialog['peer'] ?? null;
                if ($peer) {
                    $peersToResolve[$index] = $peer;
                    $dialogPeers[$index] = $dialog;
                }
            }
            
            // Update dialogs to use indexed array
            $dialogs = $dialogsArray;
            
            // Resolve all peers in a single event loop run
            // Try using getPwrChat() which might return entities directly
            $resolvedEntities = [];
            if (!empty($peersToResolve)) {
                try {
                if (!EventLoop::getDriver()) {
                        EventLoop::run(function () use ($peersToResolve, &$resolvedEntities) {
                            $futures = [];
                            foreach ($peersToResolve as $index => $peer) {
                                try {
                                    // Try getPwrChat first (returns full chat info)
                                    $pwrResult = $this->madeline->getPwrChat($peer, false);
                                    if ($pwrResult instanceof \Amp\Future) {
                                        $futures[$index] = $pwrResult;
                                    } else {
                                        $resolvedEntities[$index] = $pwrResult;
                                    }
                                } catch (\Exception $e) {
                                    // Fallback to getInfo if getPwrChat fails
                                    try {
                                        $infoResult = $this->madeline->getInfo($peer);
                                        if ($infoResult instanceof \Amp\Future) {
                                            $futures[$index] = $infoResult;
                                        } else {
                                            $resolvedEntities[$index] = $infoResult;
                                        }
                                    } catch (\Exception $e2) {
                                        Log::warning("Failed to resolve peer", [
                                            'index' => $index,
                                            'getPwrChat_error' => $e->getMessage(),
                                            'getInfo_error' => $e2->getMessage(),
                                        ]);
                                    }
                                }
                            }
                            
                            if (!empty($futures)) {
                                $results = await($futures);
                                foreach ($results as $index => $entity) {
                                    $resolvedEntities[$index] = $entity;
                                }
                            }
                    });
                } else {
                        // Already in event loop, resolve directly
                        foreach ($peersToResolve as $index => $peer) {
                            try {
                                // Try getPwrChat first
                                $pwrResult = $this->madeline->getPwrChat($peer, false);
                                if ($pwrResult instanceof \Amp\Future) {
                                    $resolvedEntities[$index] = await([$pwrResult])[0];
                                } else {
                                    $resolvedEntities[$index] = $pwrResult;
                                }
                            } catch (\Exception $e) {
                                // Fallback to getInfo
                                try {
                                    $infoResult = $this->madeline->getInfo($peer);
                                    if ($infoResult instanceof \Amp\Future) {
                                        $resolvedEntities[$index] = await([$infoResult])[0];
                                    } else {
                                        $resolvedEntities[$index] = $infoResult;
                                    }
                                } catch (\Exception $e2) {
                                    Log::warning("Failed to resolve peer", [
                                        'index' => $index,
                                        'getPwrChat_error' => $e->getMessage(),
                                        'getInfo_error' => $e2->getMessage(),
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to resolve peers batch", [
                        'channel_id' => $this->channelSource->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
            
            // Process each dialog with resolved entity
            foreach ($dialogs as $index => $dialog) {
                if (!is_array($dialog)) {
                    continue;
                }

                $entity = null;
                $entityId = null;
                $username = null;
                $title = null;
                $entityType = 'unknown';
                
                // Get resolved entity if we have it
                if (isset($resolvedEntities[$index])) {
                    $entity = $resolvedEntities[$index];
                } elseif (isset($dialog['entity'])) {
                    // Some methods might return entity directly
                    $entity = $dialog['entity'];
                } elseif (isset($dialog['_']) && !isset($dialog['peer'])) {
                    // Dialog might be the entity itself (no peer field)
                    $entity = $dialog;
                } elseif (isset($dialog['peer']) && is_array($dialog['peer'])) {
                    // Check if peer already has entity info embedded
                    // In some MadelineProto versions, peer might contain entity data
                    $peer = $dialog['peer'];
                    if (isset($peer['title']) || isset($peer['username']) || isset($peer['channel_id']) || isset($peer['chat_id'])) {
                        $entity = $peer;
                    } else {
                        // Try to get entity from peer database if available
                        try {
                            $peerId = $peer['channel_id'] ?? $peer['chat_id'] ?? $peer['user_id'] ?? null;
                            if ($peerId && method_exists($this->madeline, 'getInfo')) {
                                // Try to get info from peer database
                                $peerInfo = $this->madeline->getInfo($peer);
                                if ($peerInfo && is_array($peerInfo)) {
                                    $entity = $peerInfo;
                                }
                            }
                        } catch (\Exception $e) {
                            // Ignore - will use peer directly
                        }
                        
                        // If still no entity, use peer as entity (it has the type info)
                        if (!$entity && isset($peer['_'])) {
                            $entity = $peer;
                        }
                    }
                }
                
                // Extract info from entity
                if ($entity && is_array($entity)) {
                    // Use bot_api_id for ID (MadelineProto v8 format)
                    $entityId = $entity['bot_api_id'] ?? $entity['id'] ?? $entity['channel_id'] ?? $entity['chat_id'] ?? $entity['user_id'] ?? null;
                $username = $entity['username'] ?? null;
                $title = $entity['title'] ?? $entity['first_name'] ?? $entity['name'] ?? null;
                    $entityType = $entity['type'] ?? $entity['_'] ?? 'unknown';
                } else {
                    // Fallback: try direct extraction from dialog or peer
                    $entityId = $dialog['id'] ?? $dialog['bot_api_id'] ?? null;
                    $username = $dialog['username'] ?? null;
                    $title = $dialog['title'] ?? null;
                    $entityType = $dialog['type'] ?? $dialog['_'] ?? 'unknown';
                    
                    // If we have a peer but no entity, try extracting ID from peer
                    if (!$entityId && isset($dialog['peer']) && is_array($dialog['peer'])) {
                        $peer = $dialog['peer'];
                        $entityId = $peer['channel_id'] ?? $peer['chat_id'] ?? $peer['user_id'] ?? null;
                        $entityType = $peer['_'] ?? $entityType;
                        
                        // Also try to get title/username from dialog's top_message or other fields
                        if (!$title && isset($dialog['top_message'])) {
                            // Some dialogs might have title in other fields
                            $title = $dialog['top_message']['message'] ?? null;
                        }
                    }
                }
                
                // Final fallback: if we still don't have title but have entityId, try to construct a title
                if (!$title && $entityId) {
                    $title = 'Channel ' . $entityId;
                }
                
                // Log entity info for debugging
                Log::debug("getDialogs() - Processing dialog", [
                    'index' => $index,
                    'has_entity' => !empty($entity),
                    'entity_id' => $entityId,
                    'entity_type' => $entityType,
                    'title' => $title,
                    'username' => $username,
                    'entity_keys' => $entity && is_array($entity) ? array_keys($entity) : [],
                ]);
                
                // Only include channels and groups (not private chats/users)
                // Check entity type more comprehensively - handle all MadelineProto entity types
                $entityTypeLower = strtolower($entityType);
                
                // Check for channel types (channels and supergroups)
                // MadelineProto types: channel, supergroup, Channel, Supergroup, PeerChannel, PeerSupergroup
                $isChannel = strpos($entityTypeLower, 'channel') !== false || 
                            strpos($entityTypeLower, 'supergroup') !== false ||
                            $entityType === 'channel' ||
                            $entityType === 'supergroup' ||
                            $entityType === 'Channel' ||
                            $entityType === 'Supergroup' ||
                            $entityType === 'PeerChannel' ||
                            $entityType === 'PeerSupergroup';
                
                // Check for group types (chats and groups)
                // MadelineProto types: chat, group, megagroup, Chat, Group, Megagroup, PeerChat
                // Note: Exclude 'user' and 'PeerUser' (private chats)
                $isGroup = ($entityTypeLower !== 'user' && strpos($entityTypeLower, 'user') === false) &&
                          (strpos($entityTypeLower, 'chat') !== false || 
                           strpos($entityTypeLower, 'group') !== false ||
                           strpos($entityTypeLower, 'megagroup') !== false ||
                           $entityType === 'chat' ||
                           $entityType === 'group' ||
                           $entityType === 'megagroup' ||
                           $entityType === 'Chat' ||
                           $entityType === 'Group' ||
                           $entityType === 'Megagroup' ||
                           $entityType === 'PeerChat');
                
                // Also check peer type if entity type is unknown or if we haven't determined type yet
                if (($entityType === 'unknown' || (!$isChannel && !$isGroup)) && isset($dialog['peer'])) {
                    $peerType = $dialog['peer']['_'] ?? null;
                    if ($peerType) {
                        $peerTypeLower = strtolower($peerType);
                        // Exclude user/private chats
                        if ($peerTypeLower !== 'peeruser' && strpos($peerTypeLower, 'user') === false) {
                            $isChannel = $isChannel || strpos($peerTypeLower, 'channel') !== false || 
                                        strpos($peerTypeLower, 'supergroup') !== false ||
                                        $peerType === 'PeerChannel' ||
                                        $peerType === 'PeerSupergroup';
                            $isGroup = $isGroup || (strpos($peerTypeLower, 'chat') !== false && strpos($peerTypeLower, 'user') === false) || 
                                      strpos($peerTypeLower, 'group') !== false ||
                                      $peerType === 'PeerChat';
                            if ($entityType === 'unknown') {
                                $entityType = $peerType; // Update entity type from peer
                            }
                        }
                    }
                }
                
                // Additional check: if we have channel_id or chat_id in peer, it's likely a channel/group
                if (!$isChannel && !$isGroup && isset($dialog['peer']) && is_array($dialog['peer'])) {
                    $peer = $dialog['peer'];
                    if (isset($peer['channel_id'])) {
                        $isChannel = true;
                        $entityType = $entityType === 'unknown' ? 'PeerChannel' : $entityType;
                    } elseif (isset($peer['chat_id'])) {
                        $isGroup = true;
                        $entityType = $entityType === 'unknown' ? 'PeerChat' : $entityType;
                    }
                }
                
                // Skip if not a channel or group, or if we don't have an ID
                if (!$entityId) {
                    Log::debug("getDialogs() - Skipping dialog (no ID)", [
                        'index' => $index,
                        'entity_type' => $entityType,
                    ]);
                    continue;
                }
                
                if (!$isChannel && !$isGroup) {
                    Log::debug("getDialogs() - Skipping dialog (not channel/group)", [
                        'index' => $index,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ]);
                    continue;
                }

                    $result[] = [
                        'id' => $entityId,
                        'username' => $username,
                    'title' => $title ?: 'Unknown',
                        'type' => $entityType,
                    ];
                }
            
            Log::info("getDialogs() - Processed dialogs", [
                'channel_id' => $this->channelSource->id,
                'total_dialogs' => count($dialogs),
                'filtered_result' => count($result),
                'result_sample' => array_slice($result, 0, 5),
            ]);
            
            // If no results but we have dialogs, log why
            if (empty($result) && !empty($dialogs)) {
                Log::warning("getDialogs() - No channels/groups found despite having dialogs", [
                    'channel_id' => $this->channelSource->id,
                    'total_dialogs' => count($dialogs),
                    'sample_dialogs' => array_map(function($d, $idx) {
                        if (!is_array($d)) return ['index' => $idx, 'type' => gettype($d)];
                        $peer = $d['peer'] ?? null;
                        return [
                            'index' => $idx,
                            'keys' => array_keys($d),
                            'peer' => $peer ? (is_array($peer) ? ['type' => $peer['_'] ?? 'unknown', 'keys' => array_keys($peer)] : gettype($peer)) : null,
                            'entity' => isset($d['entity']) ? 'exists' : 'missing',
                        ];
                    }, array_slice(array_values($dialogs), 0, 5), array_keys(array_slice(array_values($dialogs), 0, 5))),
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logError("Failed to get dialogs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get dialogs in chunks for progressive loading.
     * Caches raw dialogs and processes them in batches.
     *
     * @param int $chunk Chunk number (0-based)
     * @param int $chunkSize Number of dialogs to process per chunk
     * @return array
     */
    public function getDialogsChunked(int $chunk = 0, int $chunkSize = 15): array
    {
        try {
            if (!$this->connected && !$this->connect($this->channelSource)) {
                return [
                    'dialogs' => [],
                    'has_more' => false,
                    'total_loaded' => 0,
                ];
            }

            // Create cache key based on source ID
            $cacheKey = 'telegram_dialogs_' . $this->channelSource->id;
            $cacheKeyTimestamp = $cacheKey . '_timestamp';
            $cacheKeyFetching = $cacheKey . '_fetching';
            $cacheKeyPartial = $cacheKey . '_partial'; // For first batch
            
            // Get raw dialogs from cache
            $cachedRawDialogs = \Illuminate\Support\Facades\Cache::get($cacheKey);
            $cacheAge = \Illuminate\Support\Facades\Cache::get($cacheKeyTimestamp);
            $isFetching = \Illuminate\Support\Facades\Cache::get($cacheKeyFetching);
            $partialDialogs = \Illuminate\Support\Facades\Cache::get($cacheKeyPartial);
            
            // For chunk 0: Return first batch immediately, fetch rest in background
            if ($chunk === 0) {
                // If we have full cache, use it
                if ($cachedRawDialogs !== null && $cacheAge && (time() - $cacheAge) < 3600) {
                    Log::debug("Using cached dialogs for chunk 0");
                } 
                // If we have partial cache (first batch), use it immediately
                elseif ($partialDialogs !== null) {
                    Log::debug("Using partial cached dialogs for chunk 0");
                    $cachedRawDialogs = $partialDialogs;
                }
                // If no cache and not fetching, fetch all dialogs but return first batch immediately
                elseif (!$isFetching) {
                    Log::info("Fetching dialogs for chunk 0 (will return first batch immediately)");
                    
                    // Mark as fetching to prevent duplicate requests (shorter timeout to allow retry)
                    \Illuminate\Support\Facades\Cache::put($cacheKeyFetching, true, 120); // 2 minutes
                    \Illuminate\Support\Facades\Cache::put($cacheKeyFetching . '_since', time(), 120);
                    
                    try {
                        // Note: getFullDialogs() doesn't support pagination - it fetches ALL dialogs
                        // But we'll process and return first batch immediately, cache the rest
                        $startTime = microtime(true);
                        
                        // Set a timeout for the operation (60 seconds max)
                        $timeout = 60;
                        $rawDialogs = null;
                        $fetchError = null;
                        
                        try {
                            if (!EventLoop::getDriver()) {
                                EventLoop::run(function () use (&$rawDialogs, &$fetchError, $timeout) {
                                    try {
                                        $dialogsResult = $this->madeline->getFullDialogs();
                                        if ($dialogsResult instanceof \Amp\Future) {
                                            $rawDialogs = await([$dialogsResult], $timeout * 1000)[0];
                                        } else {
                                            $rawDialogs = $dialogsResult;
                                        }
                                    } catch (\Exception $e) {
                                        $fetchError = $e;
                                    }
                                });
                            } else {
                                $dialogsResult = $this->madeline->getFullDialogs();
                                if ($dialogsResult instanceof \Amp\Future) {
                                    $rawDialogs = await([$dialogsResult], $timeout * 1000)[0];
                                } else {
                                    $rawDialogs = $dialogsResult;
                                }
                            }
                        } catch (\Amp\TimeoutException $e) {
                            Log::error("getFullDialogs() timed out after {$timeout} seconds");
                            $fetchError = new \Exception("Request timed out after {$timeout} seconds. Please try again.");
                        } catch (\Exception $e) {
                            Log::error("getFullDialogs() exception: " . $e->getMessage());
                            $fetchError = $e;
                        }
                        
                        if ($fetchError) {
                            throw $fetchError;
                        }
                        
                        $fetchTime = round((microtime(true) - $startTime) * 1000, 2);
                        // Preserve keys! getFullDialogs() returns associative array where keys are peer IDs
                        $allDialogs = is_array($rawDialogs) ? $rawDialogs : [];
                        
                        Log::info("Fetched " . count($allDialogs) . " dialogs in {$fetchTime}ms", [
                            'is_associative' => !empty($allDialogs) && array_keys($allDialogs) !== range(0, count($allDialogs) - 1),
                            'sample_keys' => array_slice(array_keys($allDialogs), 0, 5),
                        ]);
                        
                        if (empty($allDialogs)) {
                            Log::warning("getFullDialogs() returned empty array");
                            \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching);
                            \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching . '_since');
                            // Don't throw error for empty - user might not have channels
                            $cachedRawDialogs = [];
                        } else {
                            // Cache all dialogs immediately
                            \Illuminate\Support\Facades\Cache::put($cacheKey, $allDialogs, 3600);
                            \Illuminate\Support\Facades\Cache::put($cacheKeyTimestamp, time(), 3600);
                            \Illuminate\Support\Facades\Cache::forget($cacheKeyPartial);
                            \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching);
                            
                            $cachedRawDialogs = $allDialogs;
                        }
                        
                    } catch (\Exception $e) {
                        Log::error("getFullDialogs() failed: " . $e->getMessage(), [
                            'exception' => get_class($e),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Clear fetching flag so user can retry
                        \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching);
                        // Don't set empty array - let it fall through to return error
                        throw $e;
                    }
                } else {
                    // Background fetch in progress, use partial cache if available
                    if ($partialDialogs !== null) {
                        Log::debug("Background fetch in progress, using partial cache");
                        $cachedRawDialogs = $partialDialogs;
                    } else {
                        // Check if fetching flag is stale (older than 2 minutes)
                        $fetchingSince = \Illuminate\Support\Facades\Cache::get($cacheKeyFetching . '_since');
                        if ($fetchingSince && (time() - $fetchingSince) > 120) {
                            // Stale fetch flag, clear it and allow retry
                            Log::warning("Stale fetching flag detected, clearing it");
                            \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching);
                            \Illuminate\Support\Facades\Cache::forget($cacheKeyFetching . '_since');
                            $cachedRawDialogs = [];
                        } else {
                            // No cache at all, return empty with loading indicator
                            Log::info("No cache available, background fetch in progress");
                            // Return empty but indicate loading state
                            return [
                                'dialogs' => [],
                                'has_more' => false,
                                'total_loaded' => 0,
                                'loading' => true,
                                'message' => 'Loading channels, please wait a moment and refresh...',
                            ];
                        }
                    }
                }
            } else {
                // For other chunks: Use cache if available
                if ($cachedRawDialogs !== null) {
                    Log::debug("Using cached dialogs for chunk {$chunk}");
                } elseif ($partialDialogs !== null) {
                    // Use partial cache as fallback
                    Log::debug("Using partial cached dialogs for chunk {$chunk}");
                    $cachedRawDialogs = $partialDialogs;
                } else {
                    // No cache, return empty
                    $cachedRawDialogs = [];
                }
            }

            if (empty($cachedRawDialogs)) {
                // For chunk 0, if we have no cache and no dialogs, it's an error
                if ($chunk === 0) {
                    // Check if we're in a fetching state (might be in progress)
                    $isFetching = \Illuminate\Support\Facades\Cache::get($cacheKeyFetching);
                    if ($isFetching) {
                        // Fetch in progress, return empty but indicate it's loading
                        return [
                            'dialogs' => [],
                            'has_more' => false,
                            'total_loaded' => 0,
                            'loading' => true,
                            'message' => 'Loading channels, please wait...',
                        ];
                    }
                    // No cache and not fetching - this is an error
                    throw new \RuntimeException('No channels available. Please try refreshing the page or check your Telegram connection.');
                }
                // For other chunks, empty is normal (end of list)
                return [
                    'dialogs' => [],
                    'has_more' => false,
                    'total_loaded' => $chunk * $chunkSize,
                ];
            }

            // Calculate chunk range
            $startIndex = $chunk * $chunkSize;
            $endIndex = min($startIndex + $chunkSize, count($cachedRawDialogs));
            
            // getFullDialogs() returns an associative array where keys are peer IDs
            // We need to preserve both keys and values
            $chunkDialogs = [];
            $keys = array_keys($cachedRawDialogs);
            
            // Verify keys are peer IDs (should start with - for channels/groups, or be positive for users)
            // If keys are numeric indices (0, 1, 2...), the cache structure is wrong - clear it
            if (!empty($keys) && is_numeric($keys[0]) && $keys[0] >= 0 && $keys[0] < 100) {
                Log::warning("Cache structure invalid - keys are numeric indices, not peer IDs. Clearing cache.");
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                \Illuminate\Support\Facades\Cache::forget($cacheKeyTimestamp);
                \Illuminate\Support\Facades\Cache::forget($cacheKeyPartial);
                return [
                    'dialogs' => [],
                    'has_more' => false,
                    'total_loaded' => 0,
                    'loading' => true,
                    'message' => 'Cache invalid, refreshing...',
                ];
            }
            
            for ($i = $startIndex; $i < $endIndex && $i < count($keys); $i++) {
                $key = $keys[$i];
                $dialog = $cachedRawDialogs[$key];
                // Store dialog with its key (peer ID) for reference
                $chunkDialogs[] = [
                    'dialog' => $dialog,
                    'peer_id' => $key, // The key is the peer ID
                ];
            }
            
            if (empty($chunkDialogs)) {
                return [
                    'dialogs' => [],
                    'has_more' => false,
                    'total_loaded' => $chunk * $chunkSize, // Estimate based on chunk number
                ];
            }

            // Process this chunk
            // For chunk 0, skip peer resolution to speed up initial load (FAST PATH)
            // For chunk 0, we only extract basic info from peer structure without API calls
            // Subsequent chunks will resolve peers for better details
            $skipPeerResolution = ($chunk === 0);
            
            $startTime = microtime(true);
            $processedChunk = $this->processDialogsChunk($chunkDialogs, $startIndex, $skipPeerResolution);
            $processTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::debug("Processed chunk {$chunk} in {$processTime}ms (dialogs: " . count($processedChunk) . ", skip_peer_resolution: " . ($skipPeerResolution ? 'yes' : 'no') . ")");

            $hasMore = $endIndex < count($cachedRawDialogs);
            
            // Calculate total loaded based on ACTUAL processed dialogs (channels/groups that passed filtering)
            // This is the actual count, not an estimate
            $totalLoaded = count($processedChunk);

            return [
                'dialogs' => $processedChunk,
                'has_more' => $hasMore,
                'total_loaded' => $totalLoaded,
            ];

        } catch (\Exception $e) {
            $this->logError("Failed to get dialogs chunked: " . $e->getMessage());
            return [
                'dialogs' => [],
                'has_more' => false,
                'total_loaded' => 0,
            ];
        }
    }

    /**
     * Process a chunk of dialogs.
     *
     * @param array $chunkDialogs
     * @param int $startIndex Starting index in the full dialogs array
     * @param bool $skipPeerResolution Skip peer resolution for faster initial load
     * @return array
     */
    protected function processDialogsChunk(array $chunkDialogs, int $startIndex, bool $skipPeerResolution = false): array
    {
        $result = [];
        
        // Collect peers for this chunk
        $peersToResolve = [];
        $peersToResolveForTitle = []; // For lightweight title resolution when skipPeerResolution is true
        $peerIdsForTitle = []; // Store peer_id_from_key for title resolution (works for all chunks)
        
        foreach ($chunkDialogs as $localIndex => $dialogData) {
            // Handle new structure: dialogData contains 'dialog' and 'peer_id'
            if (isset($dialogData['dialog'])) {
                $dialog = $dialogData['dialog'];
                $peerIdFromKey = $dialogData['peer_id'] ?? null;
            } else {
                // Fallback: old structure (direct dialog)
                $dialog = $dialogData;
                $peerIdFromKey = null;
            }
            
            if (!is_array($dialog)) {
                continue;
            }
            
            $peer = $dialog['peer'] ?? null;
            if ($peer && is_array($peer)) {
                $peersToResolve[$localIndex] = $peer;
                // Also collect for title resolution if skipPeerResolution is true
                if ($skipPeerResolution) {
                    $peersToResolveForTitle[$localIndex] = $peer;
                }
            }
            
            // For title resolution, store peer_id_from_key for ALL chunks (can be used directly with getInfo/getPwrChat)
            // According to MadelineProto docs, getInfo() accepts peer IDs, usernames, or peer objects
            // This ensures we can resolve titles even if peer object is missing or incomplete
            if ($peerIdFromKey !== null && $peerIdFromKey !== '') {
                $peerIdsForTitle[$localIndex] = $peerIdFromKey;
            }
        }
        
        // Debug: Log peer collection results
        if ($skipPeerResolution) {
            Log::debug("Peer collection for title resolution", [
                'peersToResolveForTitle_count' => count($peersToResolveForTitle),
                'peerIdsForTitle_count' => count($peerIdsForTitle),
                'peersToResolve_count' => count($peersToResolve),
                'chunkDialogs_count' => count($chunkDialogs),
            ]);
        }

        // Batch resolve peers for title/username
        // This is needed because getFullDialogs() doesn't always include entity info
        // According to MadelineProto docs: getInfo() and getPwrChat() accept peer IDs, usernames, or peer objects
        $resolvedTitles = [];
        // Use peer_ids directly if available (more reliable than peer objects)
        // For chunk 0: use peerIdsForTitle if available, fallback to peer objects
        // For chunk 1+: always use peerIdsForTitle for consistent title resolution
        if ($skipPeerResolution) {
            $peersToResolveForTitle = !empty($peerIdsForTitle) ? $peerIdsForTitle : $peersToResolveForTitle;
        } else {
            // For subsequent chunks, use peer IDs directly (more reliable)
            $peersToResolveForTitle = $peerIdsForTitle;
        }
        
        // Resolve titles for ALL chunks (not just chunk 0)
        if (!empty($peersToResolveForTitle)) {
            try {
                // Use getPwrChat to resolve entities in batch for titles/usernames
                // Fallback to getInfo if getPwrChat fails
                if (!EventLoop::getDriver()) {
                    EventLoop::run(function () use ($peersToResolveForTitle, &$resolvedTitles) {
                        $futures = [];
                        foreach ($peersToResolveForTitle as $index => $peer) {
                            try {
                                // Try getPwrChat first (returns full chat info)
                                $pwrResult = $this->madeline->getPwrChat($peer, false);
                                if ($pwrResult instanceof \Amp\Future) {
                                    $futures[$index] = $pwrResult;
                                } else {
                                    $resolvedTitles[$index] = $pwrResult;
                                }
                            } catch (\Exception $e) {
                                // Fallback to getInfo if getPwrChat fails
                                try {
                                    $infoResult = $this->madeline->getInfo($peer);
                                    if ($infoResult instanceof \Amp\Future) {
                                        $futures[$index] = $infoResult;
                                    } else {
                                        $resolvedTitles[$index] = $infoResult;
                                    }
                                } catch (\Exception $e2) {
                                    // Both failed - log and continue
                                    Log::debug("Failed to resolve peer for title at index {$index}", [
                                        'getPwrChat_error' => $e->getMessage(),
                                        'getInfo_error' => $e2->getMessage(),
                                    ]);
                                }
                            }
                        }
                        
                        if (!empty($futures)) {
                            try {
                                $results = await($futures);
                                foreach ($results as $index => $entity) {
                                    $resolvedTitles[$index] = $entity;
                                }
                            } catch (\Exception $e) {
                                Log::debug("Failed to await futures for title resolution: " . $e->getMessage());
                            }
                        }
                    });
                } else {
                    $futures = [];
                    foreach ($peersToResolveForTitle as $index => $peer) {
                        try {
                            // Try getPwrChat first
                            $pwrResult = $this->madeline->getPwrChat($peer, false);
                            if ($pwrResult instanceof \Amp\Future) {
                                $futures[$index] = $pwrResult;
                            } else {
                                $resolvedTitles[$index] = $pwrResult;
                            }
                        } catch (\Exception $e) {
                            // Fallback to getInfo if getPwrChat fails
                            try {
                                $infoResult = $this->madeline->getInfo($peer);
                                if ($infoResult instanceof \Amp\Future) {
                                    $futures[$index] = $infoResult;
                                } else {
                                    $resolvedTitles[$index] = $infoResult;
                                }
                            } catch (\Exception $e2) {
                                // Both failed - log and continue
                                Log::debug("Failed to resolve peer for title at index {$index}", [
                                    'getPwrChat_error' => $e->getMessage(),
                                    'getInfo_error' => $e2->getMessage(),
                                ]);
                            }
                        }
                    }
                    
                    if (!empty($futures)) {
                        try {
                            $results = await($futures);
                            foreach ($results as $index => $entity) {
                                $resolvedTitles[$index] = $entity;
                            }
                        } catch (\Exception $e) {
                            Log::debug("Failed to await futures for title resolution: " . $e->getMessage());
                        }
                    }
                }
                
                Log::info("Batch resolved " . count($resolvedTitles) . " titles out of " . count($peersToResolveForTitle) . " peers", [
                    'chunk' => $skipPeerResolution ? 0 : 'subsequent',
                    'skipPeerResolution' => $skipPeerResolution,
                ]);
            } catch (\Exception $e) {
                Log::warning("Failed to batch resolve titles: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'chunk' => $skipPeerResolution ? 0 : 'subsequent',
                ]);
                // Don't fail completely - continue with fallback titles
            }
        } else {
            Log::debug("Skipping batch title resolution - no peers to resolve", [
                'peersToResolveForTitle_empty' => empty($peersToResolveForTitle),
                'skipPeerResolution' => $skipPeerResolution,
                'peerIdsForTitle_count' => count($peerIdsForTitle ?? []),
            ]);
        }

        // Resolve peers for this chunk (skip for chunk 0 to speed up initial load)
        $resolvedEntities = [];
        if (!empty($peersToResolve) && !$skipPeerResolution) {
            try {
                if (!EventLoop::getDriver()) {
                    EventLoop::run(function () use ($peersToResolve, &$resolvedEntities) {
                        $futures = [];
                        foreach ($peersToResolve as $index => $peer) {
                            try {
                                $pwrResult = $this->madeline->getPwrChat($peer, false);
                                if ($pwrResult instanceof \Amp\Future) {
                                    $futures[$index] = $pwrResult;
                                } else {
                                    $resolvedEntities[$index] = $pwrResult;
                                }
                            } catch (\Exception $e) {
                                try {
                                    $infoResult = $this->madeline->getInfo($peer);
                                    if ($infoResult instanceof \Amp\Future) {
                                        $futures[$index] = $infoResult;
                                    } else {
                                        $resolvedEntities[$index] = $infoResult;
                                    }
                                } catch (\Exception $e2) {
                                    // Use peer directly
                                    $resolvedEntities[$index] = $peer;
                                }
                            }
                        }
                        
                        if (!empty($futures)) {
                            $results = await($futures);
                            foreach ($results as $index => $entity) {
                                $resolvedEntities[$index] = $entity;
                            }
                        }
                    });
                } else {
                    // Batch resolve when EventLoop driver exists
                    $futures = [];
                    foreach ($peersToResolve as $index => $peer) {
                        try {
                            $pwrResult = $this->madeline->getPwrChat($peer, false);
                            if ($pwrResult instanceof \Amp\Future) {
                                $futures[$index] = $pwrResult;
                            } else {
                                $resolvedEntities[$index] = $pwrResult;
                            }
                        } catch (\Exception $e) {
                            try {
                                $infoResult = $this->madeline->getInfo($peer);
                                if ($infoResult instanceof \Amp\Future) {
                                    $futures[$index] = $infoResult;
                                } else {
                                    $resolvedEntities[$index] = $infoResult;
                                }
                            } catch (\Exception $e2) {
                                $resolvedEntities[$index] = $peer;
                            }
                        }
                    }
                    
                    // Await all futures at once (batch processing)
                    if (!empty($futures)) {
                        $results = await($futures);
                        foreach ($results as $index => $entity) {
                            $resolvedEntities[$index] = $entity;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to resolve peers chunk: " . $e->getMessage());
            }
        }

        // Process each dialog in chunk (reuse logic from getDialogs)
        foreach ($chunkDialogs as $localIndex => $dialogData) {
            // Handle new structure: dialogData contains 'dialog' and 'peer_id'
            if (isset($dialogData['dialog'])) {
                $dialog = $dialogData['dialog'];
                $peerIdFromKey = $dialogData['peer_id'] ?? null;
            } else {
                // Fallback: old structure (direct dialog)
                $dialog = $dialogData;
                $peerIdFromKey = null;
            }
            
            if (!is_array($dialog)) {
                continue;
            }
            
            // Debug: Log peer_id extraction for first few dialogs
            if ($skipPeerResolution && $localIndex < 3) {
                Log::debug("Extracting peer_id", [
                    'localIndex' => $localIndex,
                    'peer_id_from_key' => $peerIdFromKey,
                    'has_dialog' => isset($dialogData['dialog']),
                    'dialogData_keys' => array_keys($dialogData ?? []),
                ]);
            }
            
            // Debug: Log dialog structure for first few dialogs when skipPeerResolution is true
            if ($skipPeerResolution && $localIndex < 3) {
                $peerDebug = null;
                if (isset($dialog['peer']) && is_array($dialog['peer'])) {
                    $peerDebug = [
                        'peer_keys' => array_keys($dialog['peer']),
                        'peer_' => $dialog['peer']['_'] ?? null,
                        'peer_channel_id' => $dialog['peer']['channel_id'] ?? null,
                        'peer_chat_id' => $dialog['peer']['chat_id'] ?? null,
                        'peer_user_id' => $dialog['peer']['user_id'] ?? null,
                    ];
                }
                Log::debug("Dialog structure (skipPeerResolution=true)", [
                    'index' => $localIndex,
                    'dialog_keys' => array_keys($dialog),
                    'has_peer' => isset($dialog['peer']),
                    'has_entity' => isset($dialog['entity']),
                    'peer_id_from_key' => $peerIdFromKey,
                    'peer_details' => $peerDebug,
                ]);
            }
            
            $entity = $resolvedEntities[$localIndex] ?? null;
            $entityId = null;
            $username = null;
            $title = null;
            $entityType = 'unknown';
            $peer = null;
            $peerType = null;
            
            // First, try to use peer_id from key (getFullDialogs returns keys as peer IDs)
            // The key IS the peer ID - use it directly!
            // This is the PRIMARY source when skipPeerResolution is true
            // Check for !== null to handle 0 as a valid (though unlikely) peer ID
            if ($peerIdFromKey !== null && $peerIdFromKey !== '') {
                // The key from getFullDialogs is the peer ID
                // Format: "-1001234567890" for channels, "123456789" for users, etc.
                $entityId = (string)$peerIdFromKey;
                
                // Determine type from peer ID format
                // Negative IDs starting with -100 are channels/supergroups
                // Other negative IDs are groups
                // Positive IDs are users
                $peerIdStr = (string)$peerIdFromKey;
                if (strpos($peerIdStr, '-100') === 0) {
                    $entityType = 'channel'; // Channel or supergroup
                } elseif (strpos($peerIdStr, '-') === 0) {
                    $entityType = 'group'; // Regular group
                } elseif ($peerIdStr === '0' || $peerIdStr === '') {
                    // Invalid peer ID (0 or empty), skip it
                    continue;
                } else {
                    // Positive ID - likely a user, skip it
                    continue;
                }
                
                // When we have peer_id_from_key, try to extract title/username from dialog structure
                // but don't overwrite entityId - it's already set correctly from the key
                if ($skipPeerResolution) {
                    // Get peer structure for additional info
                    if (isset($dialog['peer']) && is_array($dialog['peer'])) {
                        $peer = $dialog['peer'];
                        $peerType = $peer['_'] ?? null;
                        
                        // Verify peer ID matches (sanity check)
                        $peerChannelId = $peer['channel_id'] ?? null;
                        $peerChatId = $peer['chat_id'] ?? null;
                        // Convert peer IDs to full format for comparison
                        // Telegram channel IDs: -100XXXXXXXXXX format
                        // Telegram chat IDs: -XXXXXXXXXX format
                        if ($peerChannelId) {
                            $fullPeerId = '-100' . abs($peerChannelId);
                            if ($fullPeerId != $peerIdFromKey && abs($peerChannelId) != abs($peerIdFromKey)) {
                                // Mismatch, but continue anyway - peer_id_from_key is authoritative
                            }
                        } elseif ($peerChatId) {
                            $fullPeerId = '-' . abs($peerChatId);
                            if ($fullPeerId != $peerIdFromKey && abs($peerChatId) != abs($peerIdFromKey)) {
                                // Mismatch, but continue anyway - peer_id_from_key is authoritative
                            }
                        }
                    }
                    
                    // Try to get title/username from dialog['entity'] if available
                    if (isset($dialog['entity']) && is_array($dialog['entity'])) {
                        $dialogEntity = $dialog['entity'];
                        $username = $dialogEntity['username'] ?? null;
                        $title = $dialogEntity['title'] ?? $dialogEntity['name'] ?? $dialogEntity['first_name'] ?? null;
                        // Don't overwrite entityType if we already determined it from peer_id
                        if ($entityType === 'unknown') {
                            $entityType = $dialogEntity['_'] ?? $dialogEntity['type'] ?? 'unknown';
                        }
                    }
                    
                    // If we still don't have title/username, use batch-resolved entity if available
                    // This is needed because getFullDialogs() doesn't always include entity info
                    if (isset($resolvedTitles[$localIndex]) && is_array($resolvedTitles[$localIndex])) {
                        $resolvedEntity = $resolvedTitles[$localIndex];
                        // Always try to get title from resolved entity if available
                        $resolvedTitle = $resolvedEntity['title'] ?? $resolvedEntity['name'] ?? $resolvedEntity['first_name'] ?? null;
                        if ($resolvedTitle && (!$title || $title === ('Channel ' . $entityId))) {
                            $title = $resolvedTitle;
                            Log::debug("Using resolved title for peer_id {$peerIdFromKey}: {$title}");
                        }
                        if (!$username) {
                            $username = $resolvedEntity['username'] ?? null;
                        }
                    } elseif ($skipPeerResolution && $localIndex < 3) {
                        Log::debug("No resolved title available for index {$localIndex}, peer_id: {$peerIdFromKey}", [
                            'has_resolvedTitles' => isset($resolvedTitles[$localIndex]),
                            'resolvedTitles_count' => count($resolvedTitles),
                            'peersToResolveForTitle_count' => count($peersToResolveForTitle ?? []),
                        ]);
                    }
                }
            }
            
            // Extract entity info (simplified version of getDialogs logic)
            // Only do this if we don't already have entityId from peer_id_from_key
            if (!$entityId) {
                // Priority 1: Use resolved entity if available (from normal peer resolution for chunk 1+)
                if ($entity && is_array($entity)) {
                    $entityId = $entity['bot_api_id'] ?? $entity['id'] ?? $entity['channel_id'] ?? $entity['chat_id'] ?? $entity['user_id'] ?? null;
                    $username = $entity['username'] ?? null;
                    $title = $entity['title'] ?? $entity['first_name'] ?? $entity['name'] ?? null;
                    $entityType = $entity['type'] ?? $entity['_'] ?? 'unknown';
                } 
                // Priority 2: For skipPeerResolution, check dialog['entity'] first (getFullDialogs provides this)
                elseif ($skipPeerResolution && isset($dialog['entity']) && is_array($dialog['entity'])) {
                    $dialogEntity = $dialog['entity'];
                    $entityId = $dialogEntity['bot_api_id'] ?? $dialogEntity['id'] ?? $dialogEntity['channel_id'] ?? $dialogEntity['chat_id'] ?? $dialogEntity['user_id'] ?? null;
                    $username = $dialogEntity['username'] ?? null;
                    $title = $dialogEntity['title'] ?? $dialogEntity['name'] ?? $dialogEntity['first_name'] ?? null;
                    $entityType = $dialogEntity['_'] ?? $dialogEntity['type'] ?? 'unknown';
                    
                    // Also get peer for type detection
                    if (isset($dialog['peer']) && is_array($dialog['peer'])) {
                        $peer = $dialog['peer'];
                        $peerType = $peer['_'] ?? null;
                    }
                } 
                // Priority 3: Extract from peer structure (when skipPeerResolution is true)
                elseif ($skipPeerResolution && isset($dialog['peer']) && is_array($dialog['peer'])) {
                    // For chunk 0, extract basic info from peer without resolution (FAST PATH)
                    $peer = $dialog['peer'];
                    $peerType = $peer['_'] ?? '';
                    
                    // Extract ID from peer - check all possible ID fields
                    // Note: getFullDialogs() returns peer structures like PeerChannel, PeerChat, PeerUser
                    if (isset($peer['channel_id'])) {
                        $entityId = $peer['channel_id'];
                    } elseif (isset($peer['chat_id'])) {
                        $entityId = $peer['chat_id'];
                    } elseif (isset($peer['user_id'])) {
                        $entityId = $peer['user_id'];
                    }
                    
                    // Also check if ID is in dialog itself (some structures have it there)
                    if (!$entityId) {
                        $entityId = $dialog['id'] ?? $dialog['channel_id'] ?? $dialog['chat_id'] ?? null;
                    }
                    
                    // Determine type from peer - use the peer type directly for better matching
                    $peerTypeLower = strtolower($peerType);
                    if (strpos($peerTypeLower, 'channel') !== false || strpos($peerTypeLower, 'supergroup') !== false) {
                        $entityType = 'channel';
                    } elseif (strpos($peerTypeLower, 'chat') !== false || strpos($peerTypeLower, 'group') !== false || strpos($peerTypeLower, 'megagroup') !== false) {
                        $entityType = 'group';
                    } else {
                        // Fallback: use peer type as-is
                        $entityType = $peerType ?: 'unknown';
                    }
                    
                    // Try to get title from dialog - check multiple possible locations
                    // Note: getFullDialogs() might not include entity field, so we need to resolve later
                    $title = $dialog['entity']['title'] ?? 
                            $dialog['entity']['name'] ?? 
                            $dialog['title'] ?? 
                            $dialog['name'] ?? 
                            null;
                    
                    // Try to get username from dialog if available
                    if (empty($username)) {
                        $username = $dialog['entity']['username'] ?? 
                                   $dialog['username'] ?? 
                                   null;
                    }
                }
            } elseif (isset($dialog['peer']) && is_array($dialog['peer'])) {
                $peer = $dialog['peer'];
                $peerType = $peer['_'] ?? 'unknown';
                $entityId = $peer['channel_id'] ?? $peer['chat_id'] ?? $peer['user_id'] ?? null;
                // Also check dialog itself
                if (!$entityId) {
                    $entityId = $dialog['id'] ?? $dialog['channel_id'] ?? $dialog['chat_id'] ?? null;
                }
                $entityType = $peerType; // Use peer type directly
            }
            
            // If we still don't have entityId, try to extract from dialog structure directly
            if (!$entityId && isset($dialog['entity']) && is_array($dialog['entity'])) {
                $entityId = $dialog['entity']['id'] ?? 
                           $dialog['entity']['bot_api_id'] ?? 
                           $dialog['entity']['channel_id'] ?? 
                           $dialog['entity']['chat_id'] ?? 
                           null;
                if (!$title && isset($dialog['entity'])) {
                    $title = $dialog['entity']['title'] ?? $dialog['entity']['name'] ?? null;
                }
                if (!$username && isset($dialog['entity'])) {
                    $username = $dialog['entity']['username'] ?? null;
                }
                if ($entityType === 'unknown' && isset($dialog['entity']['_'])) {
                    $entityType = $dialog['entity']['_'];
                }
            }
            
            // Final fallback: use peer_id from key if available (getFullDialogs keys are peer IDs)
            if (!$entityId && $peerIdFromKey) {
                $entityId = $peerIdFromKey;
            }
            
            if (!$entityId) {
                Log::debug("Skipping dialog - no entity ID", [
                    'dialog_keys' => array_keys($dialog ?? []),
                    'peer_id_from_key' => $peerIdFromKey ?? null,
                    'has_peer' => isset($dialog['peer']),
                ]);
                continue;
            }
            
            // Check if channel/group - improved detection
            $entityTypeLower = strtolower($entityType ?? '');
            
            // Get peer if not already set
            if (!$peer && isset($dialog['peer']) && is_array($dialog['peer'])) {
                $peer = $dialog['peer'];
                if (isset($peer['_'])) {
                    $peerType = $peer['_'];
                }
            }
            
            // If we already determined type from peer_id key, use it
            $isChannel = false;
            $isGroup = false;
            
            if ($entityType === 'channel') {
                $isChannel = true;
            } elseif ($entityType === 'group') {
                $isGroup = true;
            } else {
                // Try to determine from entity type string
                if (strpos($entityTypeLower, 'channel') !== false || strpos($entityTypeLower, 'supergroup') !== false) {
                    $isChannel = true;
                } elseif ((strpos($entityTypeLower, 'chat') !== false || 
                          strpos($entityTypeLower, 'group') !== false || 
                          strpos($entityTypeLower, 'megagroup') !== false) &&
                         strpos($entityTypeLower, 'user') === false &&
                         strpos($entityTypeLower, 'channel') === false) {
                    $isGroup = true;
                }
                
                // Try to determine from peer structure
                if (!$isChannel && !$isGroup && $peer) {
                    if (isset($peer['channel_id']) && !isset($peer['chat_id']) && !isset($peer['user_id'])) {
                        $isChannel = true;
                    } elseif (isset($peer['chat_id']) && !isset($peer['channel_id']) && !isset($peer['user_id'])) {
                        $isGroup = true;
                    } elseif (isset($peer['_'])) {
                        $peerTypeLower = strtolower($peer['_']);
                        if (strpos($peerTypeLower, 'channel') !== false || strpos($peerTypeLower, 'supergroup') !== false) {
                            $isChannel = true;
                        } elseif ((strpos($peerTypeLower, 'chat') !== false || 
                                  strpos($peerTypeLower, 'group') !== false || 
                                  strpos($peerTypeLower, 'megagroup') !== false) &&
                                 strpos($peerTypeLower, 'user') === false &&
                                 strpos($peerTypeLower, 'channel') === false) {
                            $isGroup = true;
                        }
                    }
                }
                
                // Final fallback: determine from entity ID format
                if (!$isChannel && !$isGroup && $entityId) {
                    $idStr = (string)$entityId;
                    // Telegram channel/group IDs: -100XXXXXXXXXX = channels, -XXXXXXXXXX = groups
                    if (strpos($idStr, '-100') === 0) {
                        $isChannel = true;
                        Log::debug("Detected channel from ID format", ['entity_id' => $entityId]);
                    } elseif (strpos($idStr, '-') === 0 && strlen($idStr) > 10) {
                        $isGroup = true;
                        Log::debug("Detected group from ID format", ['entity_id' => $entityId]);
                    }
                }
            }
            
            if (!$isChannel && !$isGroup) {
                Log::debug("Skipping dialog - not a channel or group", [
                    'entity_id' => $entityId,
                    'entity_type' => $entityType,
                    'peer_type' => $peerType ?? null,
                    'peer_id_from_key' => $peerIdFromKey ?? null,
                ]);
                continue;
            }
            
            // Final check: Use resolved title if available (for all chunks, not just chunk 0)
            // This ensures titles are used even if they weren't extracted from dialog structure
            if (isset($resolvedTitles[$localIndex]) && is_array($resolvedTitles[$localIndex])) {
                $resolvedEntity = $resolvedTitles[$localIndex];
                $resolvedTitle = $resolvedEntity['title'] ?? $resolvedEntity['name'] ?? $resolvedEntity['first_name'] ?? null;
                if ($resolvedTitle && (!$title || $title === ('Channel ' . $entityId))) {
                    $title = $resolvedTitle;
                    if ($localIndex < 3 || !$skipPeerResolution) {
                        Log::debug("Using resolved title (final check) for peer_id {$peerIdFromKey}: {$title}");
                    }
                }
                if (!$username) {
                    $username = $resolvedEntity['username'] ?? null;
                }
            }
            
            $result[] = [
                'id' => $entityId,
                'username' => $username,
                'title' => $title ?: ('Channel ' . $entityId),
                'type' => $entityType,
            ];
        }

        return $result;
    }
}
