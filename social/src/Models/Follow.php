<?php

namespace IJIDeals\Social\Models;

// use IJIDeals\UserManagement\Models\User; // Will use configured user model
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Represents a follow relationship.
 * A User (follower) follows a Followable entity (e.g., another User, a Shop, a Group).
 */
class Follow extends Model
{
    use HasFactory;

    protected $table = 'follows'; // Matches the default table name in MorphToMany

    // If you don't use created_at/updated_at for follows, set this to false.
    // public $timestamps = false;

    protected $fillable = [
        'user_id', // The ID of the User who is following
        'followable_id',
        'followable_type',
        // 'accepted_at' // Could be added if follows need approval for certain types
    ];

    /**
     * Get the user who is following.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('user-management.model', \App\Models\User::class));
    }

    /**
     * Alias for user(), more semantic in context of "follower".
     */
    public function follower(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the model that is being followed.
     */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \IJIDeals\Social\Database\factories\FollowFactory::new();
    }
}
