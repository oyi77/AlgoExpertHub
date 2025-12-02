<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TradingBotService
{
    protected $firestore = null;

    /**
     * Fetch signals from Trading Bot source.
     *
     * @param ChannelSource $channelSource
     * @return array
     */
    public function fetchSignals(ChannelSource $channelSource): array
    {
        $config = $channelSource->config ?? [];
        $signals = [];

        try {
            // Check if using Firebase or API
            if (!empty($config['firebase_project_id'])) {
                $signals = $this->fetchFromFirebase($channelSource);
            } elseif (!empty($config['api_endpoint'])) {
                $signals = $this->fetchFromApi($channelSource);
            } else {
                Log::error("TradingBot: No valid source configured", [
                    'channel_source_id' => $channelSource->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error("TradingBot: Failed to fetch signals", [
                'channel_source_id' => $channelSource->id,
                'error' => $e->getMessage()
            ]);
        }

        return $signals;
    }

    /**
     * Fetch signals from Firebase.
     *
     * @param ChannelSource $channelSource
     * @return array
     */
    protected function fetchFromFirebase(ChannelSource $channelSource): array
    {
        $config = $channelSource->config;
        $signals = [];

        // Check if Firebase package is installed
        if (!class_exists('\Kreait\Firebase\Factory')) {
            Log::error("TradingBot: Firebase package not installed. Run: composer require kreait/firebase-php", [
                'channel_source_id' => $channelSource->id
            ]);
            return [];
        }

        try {
            $firestore = $this->getFirestoreClient($channelSource);
            
            $collection = $config['firebase_collection'] ?? 'signals';
            $lastProcessedTimestamp = $config['last_processed_timestamp'] ?? null;

            $query = $firestore->database()->collection($collection)
                ->orderBy('timestamp', 'DESC')
                ->limit(100);

            if ($lastProcessedTimestamp) {
                $query = $query->where('timestamp', '>', $lastProcessedTimestamp);
            }

            $documents = $query->documents();

            foreach ($documents as $document) {
                $data = $document->data();
                $data['id'] = $document->id();
                $signals[] = $data;
            }

            // Update last processed timestamp
            if (!empty($signals)) {
                $latestTimestamp = max(array_column($signals, 'timestamp'));
                $config['last_processed_timestamp'] = $latestTimestamp;
                $channelSource->update(['config' => $config]);
            }

        } catch (\Exception $e) {
            Log::error("TradingBot: Firebase fetch error", [
                'channel_source_id' => $channelSource->id,
                'error' => $e->getMessage()
            ]);
        }

        return $signals;
    }

    /**
     * Fetch signals from API endpoint.
     *
     * @param ChannelSource $channelSource
     * @return array
     */
    protected function fetchFromApi(ChannelSource $channelSource): array
    {
        $config = $channelSource->config;
        $signals = [];

        try {
            $endpoint = $config['api_endpoint'];
            $headers = [
                'Accept' => 'application/json',
                'User-Agent' => 'Laravel-Signal-Addon/1.0'
            ];

            // Add authentication if configured
            if (!empty($config['api_token'])) {
                $authType = $config['auth_type'] ?? 'Bearer';
                $headers['Authorization'] = $authType . ' ' . $config['api_token'];
            }

            $params = [];
            if (!empty($config['last_processed_id'])) {
                $params['since_id'] = $config['last_processed_id'];
            }

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->get($endpoint, $params);

            if ($response->successful()) {
                $data = $response->json();
                
                // Handle different response formats
                if (isset($data['data'])) {
                    $signals = $data['data'];
                } elseif (isset($data['signals'])) {
                    $signals = $data['signals'];
                } elseif (is_array($data)) {
                    $signals = $data;
                }

                // Update last processed ID
                if (!empty($signals)) {
                    $lastId = end($signals)['id'] ?? null;
                    if ($lastId) {
                        $config['last_processed_id'] = $lastId;
                        $channelSource->update(['config' => $config]);
                    }
                }
            } else {
                Log::error("TradingBot: API request failed", [
                    'channel_source_id' => $channelSource->id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::error("TradingBot: API fetch error", [
                'channel_source_id' => $channelSource->id,
                'error' => $e->getMessage()
            ]);
        }

        return $signals;
    }

    /**
     * Get Firebase Firestore client.
     *
     * @param ChannelSource $channelSource
     * @return mixed
     */
    protected function getFirestoreClient(ChannelSource $channelSource)
    {
        if ($this->firestore) {
            return $this->firestore;
        }

        $config = $channelSource->config;
        $projectId = $config['firebase_project_id'];
        $credentials = $config['firebase_credentials'];

        // Handle credentials - can be JSON string or file path
        if (is_string($credentials) && file_exists($credentials)) {
            $credentialsPath = $credentials;
        } else {
            // Save credentials to temp file
            $tempFile = storage_path('app/temp/firebase_' . $channelSource->id . '.json');
            if (!is_dir(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }
            file_put_contents($tempFile, is_string($credentials) ? $credentials : json_encode($credentials));
            $credentialsPath = $tempFile;
        }

        $factory = (new \Kreait\Firebase\Factory)
            ->withServiceAccount($credentialsPath)
            ->withProjectId($projectId);

        $this->firestore = $factory->createFirestore();

        return $this->firestore;
    }

    /**
     * Test connection to Trading Bot source.
     *
     * @param ChannelSource $channelSource
     * @return array
     */
    public function testConnection(ChannelSource $channelSource): array
    {
        try {
            $config = $channelSource->config ?? [];

            if (!empty($config['firebase_project_id'])) {
                if (!class_exists('\Kreait\Firebase\Factory')) {
                    return ['success' => false, 'message' => 'Firebase package not installed. Run: composer require kreait/firebase-php'];
                }
                $firestore = $this->getFirestoreClient($channelSource);
                $collection = $config['firebase_collection'] ?? 'signals';
                $firestore->database()->collection($collection)->limit(1)->documents();
                return ['success' => true, 'message' => 'Firebase connection successful'];
            } elseif (!empty($config['api_endpoint'])) {
                $response = Http::timeout(10)->get($config['api_endpoint']);
                if ($response->successful()) {
                    return ['success' => true, 'message' => 'API connection successful'];
                }
                return ['success' => false, 'message' => 'API returned status: ' . $response->status()];
            }

            return ['success' => false, 'message' => 'No valid source configured'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

