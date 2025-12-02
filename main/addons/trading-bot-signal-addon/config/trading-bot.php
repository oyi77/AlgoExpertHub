<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase connection to fetch trading signals
    |
    */
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'signals-61284'),
        'api_key' => env('FIREBASE_API_KEY', 'AIzaSyBmmH9F51pdgm3hxH8On_wGb9WMkvn8EKs'),
        'database_url' => env('FIREBASE_DATABASE_URL', 'https://signals-61284.firebaseio.com'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'signals-61284.appspot.com'),
        // Authentication method: 'client' (mimics Android) or 'service_account' (admin SDK)
        'auth_method' => env('FIREBASE_AUTH_METHOD', 'client'),
        // Client authentication tokens (for Android client mimic) - Default tokens provided
        'auth_tokens_json' => env('FIREBASE_AUTH_TOKENS_JSON', '{"access_token":"eyJhbGciOiJSUzI1NiIsImtpZCI6IjU0NTEzMjA5OWFkNmJmNjEzODJiNmI0Y2RlOWEyZGZlZDhjYjMwZjAiLCJ0eXAiOiJKV1QifQ.eyJwcm92aWRlcl9pZCI6ImFub255bW91cyIsImlzcyI6Imh0dHBzOi8vc2VjdXJldG9rZW4uZ29vZ2xlLmNvbS9zaWduYWxzLTYxMjg0IiwiYXVkIjoic2lnbmFscy02MTI4NCIsImF1dGhfdGltZSI6MTc2MDQyOTczMCwidXNlcl9pZCI6IjBsU2NlQnJqNVFWc25MMkR6ckNhSFZkMDVkcDIiLCJzdWIiOiIwbFNjZUJyajVRVnNuTDJEenJDYUhWZDA1ZHAyIiwiaWF0IjoxNzYyMzkwNjkxLCJleHAiOjE3NjIzOTQyOTEsImZpcmViYXNlIjp7ImlkZW50aXRpZXMiOnt9LCJzaWduX2luX3Byb3ZpZGVyIjoiYW5vbnltb3VzIn19.B9enZ-6NQwGVFyPxdy6z9p9u2pzZ0luHlwwAkb77cfc0mcOqtiTM7CkXGwYrM1r7LWJWqpd3TFYIxZ3MZ-yDHbzcgoTnikx1BBG5GeLOJ4VYpHEGqNugnvQ3lObLwARzjarRkvn4HdQIG8BwJoJSk70OfDRynEDiwL4cyAqP-AjDFUSOSqd5BnDTtBuWRSjBH3d7TzAhNCTcwslRTgfBAp8qDTk2PRRptCVDxL1iS4KANbYzts1YRJtKyWoBodUpQSvr8SgGGUu-PfTIu8MmDfjlPVGdM1nligNFpxxWNcOiRAwYKvZFgg-_5ILJWNtKSzjefhD3rmkR2kTeLxH1FQ","id_token":"eyJhbGciOiJSUzI1NiIsImtpZCI6IjU0NTEzMjA5OWFkNmJmNjEzODJiNmI0Y2RlOWEyZGZlZDhjYjMwZjAiLCJ0eXAiOiJKV1QifQ.eyJwcm92aWRlcl9pZCI6ImFub255bW91cyIsImlzcyI6Imh0dHBzOi8vc2VjdXJldG9rZW4uZ29vZ2xlLmNvbS9zaWduYWxzLTYxMjg0IiwiYXVkIjoic2lnbmFscy02MTI4NCIsImF1dGhfdGltZSI6MTc2MDQyOTczMCwidXNlcl9pZCI6IjBsU2NlQnJqNVFWc25MMkR6ckNhSFZkMDVkcDIiLCJzdWIiOiIwbFNjZUJyajVRVnNuTDJEenJDYUhWZDA1ZHAyIiwiaWF0IjoxNzYyMzkwNjkxLCJleHAiOjE3NjIzOTQyOTEsImZpcmViYXNlIjp7ImlkZW50aXRpZXMiOnt9LCJzaWduX2luX3Byb3ZpZGVyIjoiYW5vbnltb3VzIn19.B9enZ-6NQwGVFyPxdy6z9p9u2pzZ0luHlwwAkb77cfc0mcOqtiTM7CkXGwYrM1r7LWJWqpd3TFYIxZ3MZ-yDHbzcgoTnikx1BBG5GeLOJ4VYpHEGqNugnvQ3lObLwARzjarRkvn4HdQIG8BwJoJSk70OfDRynEDiwL4cyAqP-AjDFUSOSqd5BnDTtBuWRSjBH3d7TzAhNCTcwslRTgfBAp8qDTk2PRRptCVDxL1iS4KANbYzts1YRJtKyWoBodUpQSvr8SgGGUu-PfTIu8MmDfjlPVGdM1nligNFpxxWNcOiRAwYKvZFgg-_5ILJWNtKSzjefhD3rmkR2kTeLxH1FQ","refresh_token":"AMf-vBz6Ag8xEgiDZ8W5W3333-pua9Z3VZ4wCPTt96phFjzChGUdBbJlJIlNlqfmbvT6QD0E9tCq-HsJl8LjR24twjNjv-2-phb2922ZnxPlpBjfmrQ_PXnVaV3Szbt6bu-uLA0gXWGC4BJPImdUD4lti6OzovhHMDwBpkRFokygW9NDlTsOfHs","expires_at":1762394291.8106098,"user_id":"0lSceBrj5QVsnL2DzrCaHVd05dp2","project_id":"727577331880"}'),
        // Service account credentials (for admin SDK - alternative method)
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase-credentials.json')),
        'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Signal Processing Configuration
    |--------------------------------------------------------------------------
    */
    'processing' => [
        'polling_interval' => env('TRADING_BOT_POLLING_INTERVAL', 90), // seconds
        'batch_size' => env('TRADING_BOT_BATCH_SIZE', 300),
        'collections' => [
            'notifications' => env('FIREBASE_NOTIFICATIONS_COLLECTION', 'notifications'),
            'signals' => env('FIREBASE_SIGNALS_COLLECTION', 'signals'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Listener Configuration
    |--------------------------------------------------------------------------
    */
    'listeners' => [
        'enabled' => env('TRADING_BOT_LISTENERS_ENABLED', true),
        'notification_listener' => env('TRADING_BOT_NOTIFICATION_LISTENER', true),
        'spot_signal_listener' => env('TRADING_BOT_SPOT_SIGNAL_LISTENER', true),
        'futures_signal_listener' => env('TRADING_BOT_FUTURES_SIGNAL_LISTENER', true),
    ],
];

