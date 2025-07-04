<?php

namespace IJIDeals\AuctionSystem\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use IJIDeals\AuctionSystem\Models\Auction;
use IJIDeals\UserManagement\Models\User;

class BidOverbidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $auction;
    protected $bidder;

    /**
     * Create a new notification instance.
     *
     * @param Auction $auction
     * @param User $bidder The user who was overbid.
     * @return void
     */
    public function __construct(Auction $auction, User $bidder)
    {
        $this->auction = $auction;
        $this->bidder = $bidder;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // This should respect user preferences from notifications-manager
        // For now, we'll assume email as a default channel.
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
        return (new MailMessage)
                    ->subject('You've been outbid on ' . $this->auction->title)
                    ->greeting('Hello ' . $this->bidder->name . '!')
                    ->line('Someone has placed a higher bid on the auction you are watching: ' . $this->auction->title . '.')
                    ->action('View Auction', url('/auctions/' . $this->auction->id))
                    ->line('The current bid is ' . $this->auction->current_bid_amount . '. Don't miss out!');
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
            'current_bid_amount' => $this->auction->current_bid_amount,
            'message' => 'You have been outbid on ' . $this->auction->title,
        ];
    }
}
