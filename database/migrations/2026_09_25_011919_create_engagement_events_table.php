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
        Schema::create('engagement_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('integration_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->char('anonymous_session_hmac', 64);
            $table->string('external_event_id')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('occurred_at');
            $table->timestampTz('received_at');
            $table->string('request_id', 100);
            $table->boolean('suspect')->default(false);

            $table->unique(['integration_id', 'external_event_id']);
            $table->index(['outlet_id', 'occurred_at']);
            $table->index('occurred_at');
        });

        DB::statement("alter table engagement_events add constraint engagement_events_event_type_check check (event_type in ('place_impression', 'place_view', 'outbound_click'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('engagement_events');
    }
};
