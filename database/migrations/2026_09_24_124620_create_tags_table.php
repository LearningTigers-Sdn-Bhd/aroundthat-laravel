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
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('status')->default('approved');
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by_business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->uuid('merged_into_id')->nullable()->index();
            $table->timestampsTz();

            $table->index(['status', 'is_active', 'name']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->foreign('merged_into_id')->references('id')->on('tags')->nullOnDelete();
        });

        Schema::create('outlet_tag', function (Blueprint $table) {
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('tag_id')->constrained()->restrictOnDelete();

            $table->primary(['outlet_id', 'tag_id']);
            $table->index('tag_id');
        });

        DB::statement("alter table tags add constraint tags_status_check check (status in ('pending', 'approved', 'rejected'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_tag');
        Schema::dropIfExists('tags');
    }
};
