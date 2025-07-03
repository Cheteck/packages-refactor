<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $ordersTable = config('ijiordermanagement.tables.orders', 'orders');
        $shopsTable = config('ijicommerce.tables.shops', 'shops');
        $usersTable = (new (config('ijicommerce.user_model', \App\Models\User::class)))->getTable();

        Schema::create($ordersTable, function (Blueprint $table) use ($shopsTable, $usersTable) {
            $table->id();
            $table->foreignId('shop_id')->constrained($shopsTable)->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained($usersTable)->onDelete('set null');

            $table->string('order_number')->unique();
            $table->string('status')->default('pending_payment');

            $table->decimal('subtotal_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('shipping_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);

            $table->string('currency', 3)->default('USD');

            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();

            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('transaction_id')->nullable();

            $table->text('notes_by_customer')->nullable();
            $table->text('notes_for_customer')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $ordersTable = config('ijiordermanagement.tables.orders', 'orders');
        Schema::dropIfExists($ordersTable);
    }
};
