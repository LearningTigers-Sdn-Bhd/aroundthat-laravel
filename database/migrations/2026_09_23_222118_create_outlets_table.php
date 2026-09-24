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
        Schema::create('outlets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postcode');
            $table->char('country_code', 2)->default('MY');
            $table->string('timezone')->default('Asia/Kuala_Lumpur');
            $table->uuid('host_outlet_id')->nullable()->index();
            $table->string('summary', 280)->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->text('google_maps_url')->nullable();
            $table->string('website', 500)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('facebook', 500)->nullable();
            $table->string('instagram', 500)->nullable();
            $table->jsonb('regular_hours')->nullable();
            $table->boolean('is_listed')->default(false);
            $table->timestampTz('hidden_at')->nullable();
            $table->text('hidden_reason')->nullable();
            $table->string('onboarding_status')->default('draft');
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->foreignUuid('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->foreignUuid('suspended_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('suspension_reason')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['business_id', 'onboarding_status', 'archived_at']);
            $table->index(['business_id', 'name']);
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->foreign('host_outlet_id')->references('id')->on('outlets')->nullOnDelete();
        });

        DB::statement("alter table outlets add constraint outlets_onboarding_status_check check (onboarding_status in ('draft', 'pending', 'approved', 'rejected'))");
        DB::statement('alter table outlets add constraint outlets_host_not_self_check check (host_outlet_id is null or host_outlet_id <> id)');
        DB::statement("alter table outlets add constraint outlets_country_code_check check (country_code ~ '^[A-Z]{2}$')");
        DB::statement('alter table outlets add constraint outlets_coordinates_paired_check check ((latitude is null) = (longitude is null))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
