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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('voucher_offer_id')->constrained()->restrictOnDelete();
            $table->char('code_hash', 64)->unique();
            $table->char('code_prefix', 4);
            $table->text('code');
            $table->string('status')->default('active');
            $table->unsignedInteger('redemption_count')->default(0);
            $table->timestampTz('expires_at');
            $table->timestampTz('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignUuid('integration_id')->nullable()->constrained()->restrictOnDelete();
            $table->char('guest_ref_hmac', 64)->nullable();
            $table->string('claim_key')->nullable();
            $table->foreignUuid('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampsTz();

            $table->index(['voucher_offer_id', 'created_at']);
            $table->unique(['integration_id', 'claim_key']);
            $table->index(['integration_id', 'guest_ref_hmac', 'voucher_offer_id']);
        });

        DB::statement("alter table vouchers add constraint vouchers_status_check check (status in ('active', 'used', 'void'))");
        DB::statement("alter table vouchers add constraint vouchers_void_check check ((status = 'void') = (voided_at is not null) and (voided_at is null) = (void_reason is null))");
        DB::statement('alter table vouchers add constraint vouchers_claim_check check (integration_id is not null or (guest_ref_hmac is null and claim_key is null and outlet_id is null))');
        DB::statement('alter table vouchers add constraint vouchers_claimed_check check (integration_id is null or (guest_ref_hmac is not null and claim_key is not null))');
        DB::statement("alter table vouchers add constraint vouchers_code_prefix_check check (code_prefix ~ '^[0-9A-HJKMNP-TV-Z]{4}$')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
