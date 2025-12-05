<?php

namespace App\Notifications;

use App\Models\Signal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignalModifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Signal $signal;
    protected array $modifications;

    public function __construct(Signal $signal, array $modifications)
    {
        $this->signal = $signal;
        $this->modifications = $modifications;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $modificationText = [];
        foreach ($this->modifications as $type => $mod) {
            if ($type === 'sl') {
                $modificationText[] = "Stop Loss: {$mod['old']} → {$mod['new']}";
            } elseif ($type === 'tp') {
                $modificationText[] = "Take Profit: {$mod['old']} → {$mod['new']}";
            } elseif ($type === 'entry_price') {
                $modificationText[] = "Entry Price: {$mod['old']} → {$mod['new']}";
            }
        }

        return [
            'signal_id' => $this->signal->id,
            'title' => 'Signal Modified: ' . $this->signal->title,
            'message' => 'Signal has been updated: ' . implode(', ', $modificationText),
            'url' => route('user.signal.show', $this->signal->id),
            'modifications' => $this->modifications,
        ];
    }
}
