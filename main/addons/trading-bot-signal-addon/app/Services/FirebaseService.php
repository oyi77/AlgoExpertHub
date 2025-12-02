<?php

namespace Addons\TradingBotSignalAddon\App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected $firestore = null;
    protected $config;
    protected $authService;
    protected $useRestApi = false;
    protected $projectId;

    public function __construct(FirebaseAuthService $authService = null)
    {
        $this->config = config('trading-bot');
        $this->authService = $authService ?? new FirebaseAuthService();
        $this->projectId = $this->config['firebase']['project_id'] ?? 'signals-61284';
        
        // Check if we should use REST API (client auth) or Admin SDK (service account)
        if ($this->authService->isAuthenticated()) {
            $this->useRestApi = true;
            Log::info('Using Firebase REST API with client authentication');
        } else {
            $this->initializeFirebase();
        }
    }

    /**
     * Initialize Firebase connection
     */
    protected function initializeFirebase(): void
    {
        if (!class_exists('\Kreait\Firebase\Factory')) {
            Log::error('Firebase package not installed. Run: composer require kreait/firebase-php');
            return;
        }

        try {
            $projectId = $this->config['firebase']['project_id'] ?? 'signals-61284';
            $credentials = $this->getCredentials();

            if (!$projectId) {
                Log::error('Firebase configuration missing: project_id');
                return;
            }

            $factory = new \Kreait\Firebase\Factory;

            // If we have service account credentials, use them (preferred for Firestore)
            if ($credentials) {
                $factory = $factory->withServiceAccount($credentials);
            } else {
                // Try to use project ID only (may have limited access)
                Log::warning('No service account credentials found. Using project ID only. Some features may be limited.');
            }

            $factory = $factory->withProjectId($projectId);
            $this->firestore = $factory->createFirestore();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Get Firebase credentials
     */
    protected function getCredentials()
    {
        // Try JSON string first
        if (!empty($this->config['firebase']['credentials_json'])) {
            $json = $this->config['firebase']['credentials_json'];
            if (is_string($json)) {
                $decoded = json_decode($json, true);
                if ($decoded) {
                    $tempFile = storage_path('app/temp/firebase_credentials_' . time() . '.json');
                    if (!is_dir(dirname($tempFile))) {
                        mkdir(dirname($tempFile), 0755, true);
                    }
                    file_put_contents($tempFile, json_encode($decoded));
                    return $tempFile;
                }
            }
        }

        // Try file path
        $path = $this->config['firebase']['credentials_path'] ?? storage_path('app/firebase-credentials.json');
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Get all notifications from Firebase with pagination
     */
    public function getAllNotifications(int $page = 1, int $perPage = 300): array
    {
        if ($this->useRestApi) {
            return $this->getAllNotificationsViaRestApi($page, $perPage);
        }

        if (!$this->firestore) {
            return [];
        }

        try {
            $collection = $this->firestore->database()
                ->collection($this->config['processing']['collections']['notifications']);

            $query = $collection->orderBy('timestamp', 'DESC')
                ->limit($perPage);

            $documents = $query->documents();
            $notifications = [];

            foreach ($documents as $document) {
                $data = $document->data();
                $data['id'] = $document->id();
                $notifications[] = $data;
            }

            return $notifications;
        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get notifications via Firebase REST API (client auth)
     */
    protected function getAllNotificationsViaRestApi(int $page = 1, int $perPage = 300): array
    {
        try {
            $collection = $this->config['processing']['collections']['notifications'];
            // Use Firestore REST API v1
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}";
            
            $headers = $this->authService->getAuthHeaders();
            if (empty($headers)) {
                Log::error('No authentication headers available');
                return [];
            }

            // Firestore REST API uses query parameters differently
            // We'll fetch and then filter/sort in PHP
            $params = [
                'pageSize' => $perPage,
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get($url, $params);

            if (!$response->successful()) {
                Log::error('Firebase REST API error: ' . $response->status() . ' - ' . $response->body());
                
                // If 403, log but continue - might be permission issue, not token issue
                if ($response->status() === 403) {
                    Log::warning('Firestore returned 403 - might be permissions or collection doesn\'t exist', [
                        'url' => $url,
                        'response' => $response->body()
                    ]);
                    return [];
                } else {
                    return [];
                }
            }

            $data = $response->json();
            $notifications = [];

            if (!empty($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    $docId = basename($doc['name']);
                    $fields = $this->convertFirestoreFields($doc['fields'] ?? []);
                    $fields['id'] = $docId;
                    $notifications[] = $fields;
                }
                
                // Sort by timestamp descending
                usort($notifications, function($a, $b) {
                    $tsA = is_numeric($a['timestamp'] ?? 0) ? $a['timestamp'] : strtotime($a['timestamp'] ?? 0);
                    $tsB = is_numeric($b['timestamp'] ?? 0) ? $b['timestamp'] : strtotime($b['timestamp'] ?? 0);
                    return $tsB <=> $tsA;
                });
            }

            return $notifications;
        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications via REST API: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all signals from Firebase with pagination
     */
    public function getAllSignals(int $page = 1, int $perPage = 300): array
    {
        if ($this->useRestApi) {
            return $this->getAllSignalsViaRestApi($page, $perPage);
        }

        if (!$this->firestore) {
            return [];
        }

        try {
            $collection = $this->firestore->database()
                ->collection($this->config['processing']['collections']['signals']);

            $query = $collection->orderBy('timestamp', 'DESC')
                ->limit($perPage);

            $documents = $query->documents();
            $signals = [];

            foreach ($documents as $document) {
                $data = $document->data();
                $data['id'] = $document->id();
                $signals[] = $data;
            }

            return $signals;
        } catch (\Exception $e) {
            Log::error('Failed to fetch signals: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get signals via Firebase REST API (client auth)
     */
    protected function getAllSignalsViaRestApi(int $page = 1, int $perPage = 300): array
    {
        try {
            $collection = $this->config['processing']['collections']['signals'];
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}";
            
            $headers = $this->authService->getAuthHeaders();
            if (empty($headers)) {
                Log::error('No authentication headers available');
                return [];
            }

            // Firestore REST API uses query parameters differently
            // We'll fetch and then filter/sort in PHP
            $params = [
                'pageSize' => $perPage,
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get($url, $params);

            if (!$response->successful()) {
                Log::error('Firebase REST API error: ' . $response->status() . ' - ' . $response->body());
                
                // If 403, log but continue - might be permission issue, not token issue
                if ($response->status() === 403) {
                    Log::warning('Firestore returned 403 - might be permissions or collection doesn\'t exist', [
                        'url' => $url,
                        'response' => $response->body()
                    ]);
                    return [];
                } else {
                    return [];
                }
            }

            $data = $response->json();
            $signals = [];

            if (!empty($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    $docId = basename($doc['name']);
                    $fields = $this->convertFirestoreFields($doc['fields'] ?? []);
                    $fields['id'] = $docId;
                    $signals[] = $fields;
                }
                
                // Sort by timestamp descending
                usort($signals, function($a, $b) {
                    $tsA = is_numeric($a['timestamp'] ?? 0) ? $a['timestamp'] : strtotime($a['timestamp'] ?? 0);
                    $tsB = is_numeric($b['timestamp'] ?? 0) ? $b['timestamp'] : strtotime($b['timestamp'] ?? 0);
                    return $tsB <=> $tsA;
                });
            }

            return $signals;
        } catch (\Exception $e) {
            Log::error('Failed to fetch signals via REST API: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Convert Firestore field format to simple array
     */
    protected function convertFirestoreFields(array $fields): array
    {
        $result = [];
        
        foreach ($fields as $key => $field) {
            if (isset($field['stringValue'])) {
                $result[$key] = $field['stringValue'];
            } elseif (isset($field['integerValue'])) {
                $result[$key] = (int)$field['integerValue'];
            } elseif (isset($field['doubleValue'])) {
                $result[$key] = (float)$field['doubleValue'];
            } elseif (isset($field['booleanValue'])) {
                $result[$key] = (bool)$field['booleanValue'];
            } elseif (isset($field['timestampValue'])) {
                $result[$key] = strtotime($field['timestampValue']);
            } elseif (isset($field['arrayValue'])) {
                $result[$key] = $this->convertFirestoreArray($field['arrayValue']);
            } elseif (isset($field['mapValue'])) {
                $result[$key] = $this->convertFirestoreFields($field['mapValue']['fields'] ?? []);
            }
        }
        
        return $result;
    }

    /**
     * Convert Firestore array value
     */
    protected function convertFirestoreArray(array $arrayValue): array
    {
        $result = [];
        foreach ($arrayValue['values'] ?? [] as $value) {
            if (isset($value['stringValue'])) {
                $result[] = $value['stringValue'];
            } elseif (isset($value['integerValue'])) {
                $result[] = (int)$value['integerValue'];
            } elseif (isset($value['doubleValue'])) {
                $result[] = (float)$value['doubleValue'];
            }
        }
        return $result;
    }

    /**
     * Get new notifications since last processed timestamp
     */
    public function getNewNotifications(?int $lastTimestamp = null): array
    {
        if ($this->useRestApi) {
            return $this->getNewNotificationsViaRestApi($lastTimestamp);
        }

        if (!$this->firestore) {
            return [];
        }

        try {
            $collection = $this->firestore->database()
                ->collection($this->config['processing']['collections']['notifications']);

            $query = $collection->orderBy('timestamp', 'DESC')
                ->limit($this->config['processing']['batch_size']);

            if ($lastTimestamp) {
                $query = $query->where('timestamp', '>', $lastTimestamp);
            }

            $documents = $query->documents();
            $notifications = [];

            foreach ($documents as $document) {
                $data = $document->data();
                $data['id'] = $document->id();
                $notifications[] = $data;
            }

            return $notifications;
        } catch (\Exception $e) {
            Log::error('Failed to fetch new notifications: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get new notifications via REST API
     */
    protected function getNewNotificationsViaRestApi(?int $lastTimestamp = null): array
    {
        try {
            $collection = $this->config['processing']['collections']['notifications'];
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}";
            
            $headers = $this->authService->getAuthHeaders();
            if (empty($headers)) {
                return [];
            }

            $params = [
                'pageSize' => $this->config['processing']['batch_size'],
                'orderBy' => 'timestamp desc',
            ];

            // Note: Firestore REST API filtering is complex, so we fetch and filter in PHP
            $response = Http::withHeaders($headers)->get($url, $params);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $notifications = [];

            if (!empty($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    $docId = basename($doc['name']);
                    $fields = $this->convertFirestoreFields($doc['fields'] ?? []);
                    $fields['id'] = $docId;
                    
                    // Filter by timestamp if provided
                    if ($lastTimestamp && isset($fields['timestamp'])) {
                        $ts = is_numeric($fields['timestamp']) ? $fields['timestamp'] : strtotime($fields['timestamp']);
                        if ($ts <= $lastTimestamp) {
                            continue;
                        }
                    }
                    
                    $notifications[] = $fields;
                }
            }

            return $notifications;
        } catch (\Exception $e) {
            Log::error('Failed to fetch new notifications via REST API: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get new signals since last processed timestamp
     */
    public function getNewSignals(?int $lastTimestamp = null): array
    {
        if ($this->useRestApi) {
            return $this->getNewSignalsViaRestApi($lastTimestamp);
        }

        if (!$this->firestore) {
            return [];
        }

        try {
            $collection = $this->firestore->database()
                ->collection($this->config['processing']['collections']['signals']);

            $query = $collection->orderBy('timestamp', 'DESC')
                ->limit($this->config['processing']['batch_size']);

            if ($lastTimestamp) {
                $query = $query->where('timestamp', '>', $lastTimestamp);
            }

            $documents = $query->documents();
            $signals = [];

            foreach ($documents as $document) {
                $data = $document->data();
                $data['id'] = $document->id();
                $signals[] = $data;
            }

            return $signals;
        } catch (\Exception $e) {
            Log::error('Failed to fetch new signals: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get new signals via REST API
     */
    protected function getNewSignalsViaRestApi(?int $lastTimestamp = null): array
    {
        try {
            $collection = $this->config['processing']['collections']['signals'];
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}";
            
            $headers = $this->authService->getAuthHeaders();
            if (empty($headers)) {
                return [];
            }

            // Firestore REST API uses query parameters differently
            // We'll fetch and then filter/sort in PHP
            $params = [
                'pageSize' => $this->config['processing']['batch_size'],
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get($url, $params);

            if (!$response->successful()) {
                Log::error('Firebase REST API error: ' . $response->status() . ' - ' . $response->body());
                return [];
            }

            $data = $response->json();
            $signals = [];

            if (!empty($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    $docId = basename($doc['name']);
                    $fields = $this->convertFirestoreFields($doc['fields'] ?? []);
                    $fields['id'] = $docId;
                    
                    // Filter by timestamp if provided
                    if ($lastTimestamp && isset($fields['timestamp'])) {
                        $ts = is_numeric($fields['timestamp']) ? $fields['timestamp'] : strtotime($fields['timestamp']);
                        if ($ts <= $lastTimestamp) {
                            continue;
                        }
                    }
                    
                    $signals[] = $fields;
                }
                
                // Sort by timestamp descending
                usort($signals, function($a, $b) {
                    $tsA = is_numeric($a['timestamp'] ?? 0) ? $a['timestamp'] : strtotime($a['timestamp'] ?? 0);
                    $tsB = is_numeric($b['timestamp'] ?? 0) ? $b['timestamp'] : strtotime($b['timestamp'] ?? 0);
                    return $tsB <=> $tsA;
                });
            }

            return $signals;
        } catch (\Exception $e) {
            Log::error('Failed to fetch new signals via REST API: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Test Firebase connection
     */
    public function testConnection(): array
    {
        if ($this->useRestApi) {
            try {
                $headers = $this->authService->getAuthHeaders();
                if (empty($headers)) {
                    return ['success' => false, 'message' => 'No authentication headers available'];
                }

                // Try Firestore first
                $collection = $this->config['processing']['collections']['notifications'];
                $firestoreUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}";
                
                $response = Http::withHeaders($headers)->timeout(10)->get($firestoreUrl, ['pageSize' => 1]);
                
                if ($response->successful()) {
                    return ['success' => true, 'message' => 'Firestore REST API connection successful'];
                }
                
                // If Firestore fails, try Realtime Database
                $dbUrl = $this->config['firebase']['database_url'] ?? "https://{$this->projectId}.firebaseio.com";
                $realtimeUrl = rtrim($dbUrl, '/') . "/{$collection}.json";
                
                $realtimeResponse = Http::withHeaders($headers)->timeout(10)->get($realtimeUrl, ['auth' => $this->authService->getAccessToken()]);
                
                if ($realtimeResponse->successful()) {
                    return ['success' => true, 'message' => 'Realtime Database REST API connection successful'];
                }
                
                return [
                    'success' => false, 
                    'message' => 'Both Firestore and Realtime Database failed. Firestore: ' . $response->status() . ', Realtime: ' . $realtimeResponse->status()
                ];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        if (!$this->firestore) {
            return ['success' => false, 'message' => 'Firebase not initialized'];
        }

        try {
            $collection = $this->firestore->database()
                ->collection($this->config['processing']['collections']['notifications']);
            $collection->limit(1)->documents();
            return ['success' => true, 'message' => 'Connection successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

