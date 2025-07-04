<?php

namespace IJIDeals\ReturnsManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use IJIDeals\UserManagement\Models\User;

class ReturnStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_request_id',
        'old_status',
        'new_status',
        'changed_by_user_id',
        'notes',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
