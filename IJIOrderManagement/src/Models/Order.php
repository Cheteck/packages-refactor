<?php

namespace IJIDeals\IJIOrderManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'order_number',
        'status',
        'total_amount',
        'currency',
        'billing_address',
        'shipping_address',
        'payment_method',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(6));
            }
            if (empty($order->status)) {
                $order->status = 'pending_payment';
            }
            if (empty($order->payment_status)) {
                $order->payment_status = 'pending';
            }
            Log::info('Creating new order.', ['order_number' => $order->order_number, 'shop_id' => $order->shop_id, 'user_id' => $order->user_id]);
        });

        static::updating(function ($order) {
            Log::info('Updating order.', ['id' => $order->id, 'order_number' => $order->order_number, 'changes' => $order->getDirty()]);
        });

        static::deleting(function ($order) {
            Log::info('Deleting order.', ['id' => $order->id, 'order_number' => $order->order_number]);
        });
    }

    public function getTable()
    {
        return config('ijiordermanagement.tables.orders', 'orders');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\IJIDeals\IJICommerce\Models\Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('ijicommerce.user_model', \App\Models\User::class), 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
