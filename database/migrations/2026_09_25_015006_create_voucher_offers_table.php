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
        Schema::create('voucher_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discount_type');
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->decimal('min_spend_amount', 12, 2)->nullable();
            $table->string('free_item')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->unsignedInteger('voucher_valid_days')->nullable();
            $table->unsignedInteger('voucher_limit')->nullable();
            $table->unsignedInteger('issued_count')->default(0);
            $table->unsignedInteger('uses_per_voucher')->default(1);
            $table->string('status')->default('draft');
            $table->timestampTz('hidden_at')->nullable();
            $table->text('hidden_reason')->nullable();
            $table->timestampsTz();

            $table->index(['business_id', 'status']);
            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('outlet_voucher_offer', function (Blueprint $table) {
            $table->foreignUuid('voucher_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['voucher_offer_id', 'outlet_id']);
            $table->index('outlet_id');
        });

        DB::statement("alter table voucher_offers add constraint voucher_offers_discount_type_check check (discount_type in ('percentage', 'amount', 'free_item'))");
        DB::statement("alter table voucher_offers add constraint voucher_offers_status_check check (status in ('draft', 'active', 'paused'))");
        DB::statement("alter table voucher_offers add constraint voucher_offers_percentage_check check (discount_type <> 'percentage' or discount_value between 0.01 and 100)");
        DB::statement("alter table voucher_offers add constraint voucher_offers_amount_check check (discount_type <> 'amount' or discount_value > 0)");
        DB::statement("alter table voucher_offers add constraint voucher_offers_free_item_check check ((discount_type = 'free_item') = (free_item is not null) and (discount_type = 'free_item') = (discount_value is null))");
        DB::statement("alter table voucher_offers add constraint voucher_offers_max_discount_check check (max_discount_amount is null or (discount_type = 'percentage' and max_discount_amount > 0))");
        DB::statement('alter table voucher_offers add constraint voucher_offers_min_spend_check check (min_spend_amount is null or min_spend_amount > 0)');
        DB::statement('alter table voucher_offers add constraint voucher_offers_window_check check (ends_at > starts_at)');
        DB::statement('alter table voucher_offers add constraint voucher_offers_uses_check check (uses_per_voucher >= 1)');
        DB::statement('alter table voucher_offers add constraint voucher_offers_limit_check check (voucher_limit is null or (voucher_limit >= 1 and issued_count <= voucher_limit))');
        DB::statement('alter table voucher_offers add constraint voucher_offers_valid_days_check check (voucher_valid_days is null or voucher_valid_days >= 1)');
        DB::statement('alter table voucher_offers add constraint voucher_offers_hidden_check check ((hidden_at is null) = (hidden_reason is null))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_voucher_offer');
        Schema::dropIfExists('voucher_offers');
    }
};
