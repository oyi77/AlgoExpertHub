<?php

namespace App\Helpers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

/**
 * Helper class for migrating from old notification system to Laravel Notify
 * 
 * This provides backward-compatible methods that work with both old and new systems
 */
class NotificationHelper
{
    /**
     * Flash a success notification
     * 
     * @param string $message
     * @param string|null $title
     * @return array
     */
    public static function success(string $message, ?string $title = null): array
    {
        return [
            'type' => 'success',
            'title' => $title ?? 'Success',
            'message' => $message,
        ];
    }

    /**
     * Flash an error notification
     * 
     * @param string $message
     * @param string|null $title
     * @return array
     */
    public static function error(string $message, ?string $title = null): array
    {
        return [
            'type' => 'error',
            'title' => $title ?? 'Error',
            'message' => $message,
        ];
    }

    /**
     * Flash a warning notification
     * 
     * @param string $message
     * @param string|null $title
     * @return array
     */
    public static function warning(string $message, ?string $title = null): array
    {
        return [
            'type' => 'warning',
            'title' => $title ?? 'Warning',
            'message' => $message,
        ];
    }

    /**
     * Flash an info notification
     * 
     * @param string $message
     * @param string|null $title
     * @return array
     */
    public static function info(string $message, ?string $title = null): array
    {
        return [
            'type' => 'info',
            'title' => $title ?? 'Info',
            'message' => $message,
        ];
    }

    /**
     * Add notification to redirect response (new Laravel Notify way)
     * 
     * @param RedirectResponse $redirect
     * @param string $type
     * @param string $message
     * @param string|null $title
     * @return RedirectResponse
     */
    public static function withNotify(
        RedirectResponse $redirect,
        string $type,
        string $message,
        ?string $title = null
    ): RedirectResponse {
        $redirect->with('notify', [
            'type' => $type,
            'title' => $title ?? ucfirst($type),
            'message' => $message,
        ]);

        // Also set legacy session for backward compatibility
        $redirect->with($type, $message);

        return $redirect;
    }

    /**
     * Helper to convert old ->with('success') pattern to new notify pattern
     * 
     * Usage in controllers:
     * return NotificationHelper::notify('success', 'User updated successfully', 'User Updated');
     * 
     * @param string $type success|error|warning|info
     * @param string $message
     * @param string|null $title
     * @return array
     */
    public static function notify(string $type, string $message, ?string $title = null): array
    {
        return [
            'type' => $type,
            'title' => $title ?? ucfirst($type),
            'message' => $message,
        ];
    }
}

