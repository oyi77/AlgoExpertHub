<?php

namespace Addons\TradingManagement\Modules\Backtesting\Notifications;

use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BacktestCompletedNotification extends Notification
{
    use Queueable;

    protected $backtest;

    /**
     * Create a new notification instance.
     */
    public function __construct(Backtest $backtest)
    {
        $this->backtest = $backtest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Backtest Completed: ' . $this->backtest->symbol)
            ->line('Your backtest for ' . $this->backtest->symbol . ' on ' . $this->backtest->timeframe . ' has completed.')
            ->line('Net Profit: ' . ($this->backtest->result->net_profit ?? 'N/A'))
            ->line('Win Rate: ' . ($this->backtest->result->win_rate ?? 'N/A') . '%')
            ->action('View Results', route('user.trading.backtesting.show', $this->backtest->id))
            ->line('Thank you for using our trading bot!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'backtest_id' => $this->backtest->id,
            'symbol' => $this->backtest->symbol,
            'timeframe' => $this->backtest->timeframe,
            'status' => 'completed',
            'message' => 'Backtest for ' . $this->backtest->symbol . ' completed successfully.',
        ];
    }
}
