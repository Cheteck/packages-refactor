<?php

namespace IJIDeals\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyMentionedUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    protected $mentionedUsers;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $post
     * @return void
     */
    public function __construct($post, array $mentionedUsers)
    {
        $this->post = $post;
        $this->mentionedUsers = $mentionedUsers;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Placeholder for notifying mentioned users
        Log::info('NotifyMentionedUsers job handled', [
            'post_id' => $this->post->id,
            'mentioned_users' => $this->mentionedUsers,
        ]);
    }
}
