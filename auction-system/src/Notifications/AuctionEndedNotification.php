<?php

namespace IJIDeals\AuctionSystem\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use IJIDeals\AuctionSystem\Models\Auction;
use IJIDeals\UserManagement\Models\User;

class AuctionEndedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $auction;
    protected $user;
    protected $isWinner;

    /**
     * Create a new notification instance.
     *
     * @param Auction $auction
     * @param User $user The user to notify (winner or loser).
     * @param bool $isWinner True if the user is the winner, false otherwise.
     * @return void
     */
    public function __construct(Auction $auction, User $user, bool $isWinner)
    {
        $this->auction = $auction;
        $this->user = $user;
        $this->isWinner = $isWinner;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if ($this->isWinner) {
            return (new MailMessage)
                        ->subject('Congratulations! You won the auction for ' . $this->auction->title)
                        ->greeting('Hello ' . $this->user->name . '!')
                        ->line('You have successfully won the auction for: ' . $this->auction->title . '.')
                        ->line('Your winning bid was ' . $this->auction->current_bid_amount . '.')
                        ->action('View Auction Details', url('/auctions/' . $this->auction->id . '/won'))
                        ->line('Please proceed to payment to claim your item.');
        } else {
            return (new MailMessage)
                        ->subject('Auction Ended: ' . $this->auction->title)
                        ->greeting('Hello ' . $this->user->name . '!')
                        ->line('The auction for ' . $this->auction->title . ' has ended.')
                        ->line('Unfortunately, your bid was not the winning one.')
                        ->action('View Auction', url('/auctions/' . $this->auction->id))
                        ->line('Thank you for participating!');
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'auction_id' => $this->auction->id,
            'auction_title' => $this->auction->title,
            'is_winner' => $this->isWinner,
            'message' => $this->isWinner ? 
                'You won the auction for ' . $this->auction->title : 
                'The auction for ' . $this->auction->title . ' has ended.',
        ];
    }
}
