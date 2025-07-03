<?php

namespace IJIDeals\Social\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HighEngagementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $score;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(float $score)
    {
        $this->score = $score;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('Your content is receiving high engagement!')
            ->line('Current engagement score: '.$this->score)
            ->action('View Content', url('/')) // Replace with actual URL
            ->line('Keep up the great work!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'score' => $this->score,
            'message' => 'Your content is receiving high engagement!',
        ];
    }
}
