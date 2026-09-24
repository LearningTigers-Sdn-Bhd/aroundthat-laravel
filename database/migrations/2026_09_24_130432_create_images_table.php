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
        Schema::create('images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('imageable');
            $table->string('kind');
            $table->string('disk');
            $table->string('path');
            $table->string('alt_text', 250);
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('position')->default(0);
            $table->timestampTz('removed_at')->nullable()->index();
            $table->timestampsTz();

            $table->index(['imageable_type', 'imageable_id', 'kind', 'removed_at']);
        });

        DB::statement("alter table images add constraint images_kind_check check (kind in ('cover', 'gallery', 'logo'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
