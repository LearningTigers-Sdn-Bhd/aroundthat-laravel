<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('redemptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('voucher_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('bill_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2);
            $table->boolean('capped')->default(false);
            $table->uuid('idempotency_key');
            $table->timestampTz('redeemed_at');
            $table->timestampTz('cancelled_at')->nullable();
            $table->foreignUuid('cancelled_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->timestampsTz();

            $table->unique(['outlet_id', 'idempotency_key']);
            $table->index(['outlet_id', 'redeemed_at']);
            $table->index('voucher_id');
        });

        DB::statement('alter table redemptions add constraint redemptions_amounts_check check (bill_amount > 0 and discount_amount >= 0 and discount_amount <= bill_amount)');
        DB::statement('alter table redemptions add constraint redemptions_cancel_check check ((cancelled_at is null) = (cancel_reason is null))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redemptions');
    }
};
