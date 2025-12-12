<?php

namespace App\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class RealTimeFeedbackService extends BaseService
{
    /**
     * Create a loading state for a specific action.
     *
     * @param string $actionId
     * @param string $message
     * @param array $options
     * @return array
     */
    public function createLoadingState(string $actionId, string $message = 'Loading...', array $options = []): array
    {
        $loadingState = [
            'id' => $actionId,
            'message' => $message,
            'progress' => $options['progress'] ?? null,
            'type' => $options['type'] ?? 'default',
            'cancelable' => $options['cancelable'] ?? false,
            'started_at' => now()->toISOString(),
            'estimated_duration' => $options['estimated_duration'] ?? null,
        ];

        // Store in session for current user
        $this->storeLoadingState($actionId, $loadingState);

        return $loadingState;
    }

    /**
     * Update loading state progress.
     *
     * @param string $actionId
     * @param int $progress
     * @param string|null $message
     * @return array|null
     */
    public function updateLoadingProgress(string $actionId, int $progress, ?string $message = null): ?array
    {
        $loadingState = $this->getLoadingState($actionId);
        
        if (!$loadingState) {
            return null;
        }

        $loadingState['progress'] = max(0, min(100, $progress));
        
        if ($message) {
            $loadingState['message'] = $message;
        }

        $loadingState['updated_at'] = now()->toISOString();

        $this->storeLoadingState($actionId, $loadingState);

        return $loadingState;
    }

    /**
     * Complete a loading state.
     *
     * @param string $actionId
     * @param string $message
     * @param string $type
     * @return array
     */
    public function completeLoadingState(string $actionId, string $message = 'Completed', string $type = 'success'): array
    {
        $completionState = [
            'id' => $actionId,
            'message' => $message,
            'type' => $type,
            'completed_at' => now()->toISOString(),
            'duration' => $this->calculateDuration($actionId),
        ];

        // Remove loading state
        $this->removeLoadingState($actionId);

        // Add to completion history
        $this->addCompletionHistory($actionId, $completionState);

        return $completionState;
    }

    /**
     * Create a notification.
     *
     * @param string $message
     * @param string $type
     * @param array $options
     * @return array
     */
    public function createNotification(string $message, string $type = 'info', array $options = []): array
    {
        $notification = [
            'id' => uniqid('notification_'),
            'message' => $message,
            'type' => $type, // success, error, warning, info
            'title' => $options['title'] ?? null,
            'persistent' => $options['persistent'] ?? false,
            'auto_dismiss' => $options['auto_dismiss'] ?? true,
            'dismiss_after' => $options['dismiss_after'] ?? 5000, // milliseconds
            'actions' => $options['actions'] ?? [],
            'created_at' => now()->toISOString(),
        ];

        // Store notification
        $this->storeNotification($notification);

        return $notification;
    }

    /**
     * Create form validation feedback.
     *
     * @param array $errors
     * @param array $options
     * @return array
     */
    public function createFormValidationFeedback(array $errors, array $options = []): array
    {
        $feedback = [
            'type' => 'validation_error',
            'errors' => $this->formatValidationErrors($errors),
            'summary' => $this->generateValidationSummary($errors),
            'field_count' => count($errors),
            'created_at' => now()->toISOString(),
            'options' => [
                'highlight_fields' => $options['highlight_fields'] ?? true,
                'scroll_to_first_error' => $options['scroll_to_first_error'] ?? true,
                'show_summary' => $options['show_summary'] ?? true,
            ],
        ];

        return $feedback;
    }

    /**
     * Get user action feedback configuration.
     *
     * @return array
     */
    public function getUserActionFeedbackConfig(): array
    {
        return [
            'loading_states' => [
                'show_progress' => true,
                'show_estimated_time' => true,
                'allow_cancellation' => true,
                'min_display_time' => 500, // milliseconds
            ],
            'notifications' => [
                'position' => 'top-right',
                'max_visible' => 5,
                'auto_dismiss' => true,
                'dismiss_after' => 5000,
                'sound_enabled' => false,
            ],
            'form_validation' => [
                'real_time' => true,
                'debounce_delay' => 300,
                'highlight_errors' => true,
                'show_success_indicators' => true,
            ],
            'animations' => [
                'enabled' => true,
                'duration' => 300,
                'easing' => 'ease-in-out',
            ],
        ];
    }

    /**
     * Get loading indicators configuration.
     *
     * @return array
     */
    public function getLoadingIndicatorsConfig(): array
    {
        return [
            'spinner' => [
                'type' => 'dots',
                'size' => 'medium',
                'color' => 'primary',
            ],
            'progress_bar' => [
                'show_percentage' => true,
                'show_estimated_time' => true,
                'animated' => true,
            ],
            'skeleton' => [
                'enabled' => true,
                'animation' => 'pulse',
                'lines' => 3,
            ],
            'button_states' => [
                'loading_text' => 'Processing...',
                'disable_on_submit' => true,
                'show_spinner' => true,
            ],
        ];
    }

    /**
     * Store loading state in session.
     *
     * @param string $actionId
     * @param array $state
     */
    private function storeLoadingState(string $actionId, array $state): void
    {
        $loadingStates = Session::get('loading_states', []);
        $loadingStates[$actionId] = $state;
        Session::put('loading_states', $loadingStates);
    }

    /**
     * Get loading state from session.
     *
     * @param string $actionId
     * @return array|null
     */
    private function getLoadingState(string $actionId): ?array
    {
        $loadingStates = Session::get('loading_states', []);
        return $loadingStates[$actionId] ?? null;
    }

    /**
     * Remove loading state from session.
     *
     * @param string $actionId
     */
    private function removeLoadingState(string $actionId): void
    {
        $loadingStates = Session::get('loading_states', []);
        unset($loadingStates[$actionId]);
        Session::put('loading_states', $loadingStates);
    }

    /**
     * Store notification in session.
     *
     * @param array $notification
     */
    private function storeNotification(array $notification): void
    {
        $notifications = Session::get('notifications', []);
        $notifications[] = $notification;
        
        // Keep only last 10 notifications
        if (count($notifications) > 10) {
            $notifications = array_slice($notifications, -10);
        }
        
        Session::put('notifications', $notifications);
    }

    /**
     * Calculate duration for an action.
     *
     * @param string $actionId
     * @return int|null
     */
    private function calculateDuration(string $actionId): ?int
    {
        $loadingState = $this->getLoadingState($actionId);
        
        if (!$loadingState || !isset($loadingState['started_at'])) {
            return null;
        }

        $startTime = \Carbon\Carbon::parse($loadingState['started_at']);
        return $startTime->diffInMilliseconds(now());
    }

    /**
     * Add completion to history.
     *
     * @param string $actionId
     * @param array $completion
     */
    private function addCompletionHistory(string $actionId, array $completion): void
    {
        $history = Session::get('completion_history', []);
        $history[] = $completion;
        
        // Keep only last 20 completions
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }
        
        Session::put('completion_history', $history);
    }

    /**
     * Format validation errors for frontend consumption.
     *
     * @param array $errors
     * @return array
     */
    private function formatValidationErrors(array $errors): array
    {
        $formatted = [];
        
        foreach ($errors as $field => $messages) {
            $formatted[$field] = [
                'field' => $field,
                'messages' => is_array($messages) ? $messages : [$messages],
                'first_message' => is_array($messages) ? $messages[0] : $messages,
                'count' => is_array($messages) ? count($messages) : 1,
            ];
        }
        
        return $formatted;
    }

    /**
     * Generate validation summary.
     *
     * @param array $errors
     * @return string
     */
    private function generateValidationSummary(array $errors): string
    {
        $count = count($errors);
        
        if ($count === 1) {
            return 'Please fix 1 error below.';
        }
        
        return "Please fix {$count} errors below.";
    }
}