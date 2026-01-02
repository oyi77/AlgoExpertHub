<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Notification Model
    |--------------------------------------------------------------------------
    |
    | This option controls the default notification model that will be used
    | by the notify helper. You may change this to any of the available
    | models: toast, connect, drake, smiley, emotify
    |
    */

    'default_model' => 'toast',

    /*
    |--------------------------------------------------------------------------
    | Default Notification Type
    |--------------------------------------------------------------------------
    |
    | This option controls the default notification type that will be used
    | when no type is specified. Available types: success, error, warning, info
    |
    */

    'default_type' => 'success',

    /*
    |--------------------------------------------------------------------------
    | Notification Timeout
    |--------------------------------------------------------------------------
    |
    | This option controls how long (in milliseconds) notifications will
    | be displayed before automatically closing. Set to a very high number
    | to keep notifications open until manually closed.
    |
    */

    'timeout' => env('NOTIFY_TIMEOUT', 5000),

    /*
    |--------------------------------------------------------------------------
    | Preset Messages
    |--------------------------------------------------------------------------
    |
    | Define commonly used notification messages here for easy reuse
    | throughout your application using notify()->preset('key')->send()
    |
    */

    'preset-messages' => [
        'user-updated' => [
            'type'    => 'success',
            'model'   => 'toast',
            'title'   => 'User Updated',
            'message' => 'The user has been updated successfully.',
        ],
        'user-deleted' => [
            'type'    => 'success',
            'model'   => 'toast',
            'title'   => 'User Deleted',
            'message' => 'The user has been deleted successfully.',
        ],
        'signal-published' => [
            'type'    => 'success',
            'model'   => 'toast',
            'title'   => 'Signal Published',
            'message' => 'The signal has been published successfully.',
        ],
        'payment-success' => [
            'type'    => 'success',
            'model'   => 'toast',
            'title'   => 'Payment Successful',
            'message' => 'Your payment has been processed successfully.',
        ],
        'payment-error' => [
            'type'    => 'error',
            'model'   => 'toast',
            'title'   => 'Payment Failed',
            'message' => 'There was an error processing your payment. Please try again.',
        ],
        'subscription-activated' => [
            'type'    => 'success',
            'model'   => 'toast',
            'title'   => 'Subscription Activated',
            'message' => 'Your subscription has been activated successfully.',
        ],
    ],
];

