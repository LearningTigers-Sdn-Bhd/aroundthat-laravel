<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableUuidMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableUuidMorphs('causer', 'causer');
            $table->jsonb('attribute_changes')->nullable();
            $table->jsonb('properties')->nullable();
            $table->text('reason')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->uuid('reviewed_by_id')->nullable()->index();
            $table->timestampTz('reverted_at')->nullable();
            $table->foreignId('reverted_by_activity_id')->nullable()->constrained('activity_log')->nullOnDelete();
            $table->timestampsTz();

            $table->index('created_at');
            $table->index(['log_name', 'reviewed_at']);
        });
    }
};
