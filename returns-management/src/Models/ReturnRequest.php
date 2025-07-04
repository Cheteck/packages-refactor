<?php

namespace IJIDeals\ReturnsManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use IJIDeals\UserManagement\Models\User;
use IJIDeals\IJIOrderManagement\Models\Order;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'return_number',
        'status',
        'reason',
        'customer_notes',
        'admin_notes',
        'requested_at',
        'approved_at',
        'received_at',
        'refunded_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($returnRequest) {
            if (empty($returnRequest->return_number)) {
                $returnRequest->return_number = 'RMA-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
            }
        });

        static::updating(function ($returnRequest) {
            if ($returnRequest->isDirty('status')) {
                $returnRequest->statusLogs()->create([
                    'old_status' => $returnRequest->getOriginal('status'),
                    'new_status' => $returnRequest->status,
                    'changed_by_user_id' => auth()->id(),
                ]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReturnStatusLog::class);
    }
}
