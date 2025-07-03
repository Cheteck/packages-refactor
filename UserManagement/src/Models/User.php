<?php

namespace IJIDeals\UserManagement\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username', // Retaining username as it's common for social features
        'profile_photo_path',
        'cover_photo_path',
        'bio',
        'birthdate',
        'gender',
        'phone',
        'preferred_language',
        'location',
        'website',
        // followers_count and following_count are typically not mass assignable directly
        // but updated via specific methods or events.
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthdate' => 'date', // Casting birthdate to a Carbon date object
        'followers_count' => 'integer',
        'following_count' => 'integer',
    ];

    // Add relationships here if needed, for example:
    // public function posts()
    // {
    //     return $this->hasMany(Post::class);
    // }
}
