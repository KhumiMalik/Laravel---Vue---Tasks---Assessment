<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Leading user_id lets MySQL reuse these for the FK, so no standalone user_id index.
            // (user_id, is_completed) serves the list/status filter and the stats count;
            // (user_id, deleted_at) serves the trash listing.
            // Title search is LIKE '%term%', which a B-tree index can't help with.
            $table->index(['user_id', 'is_completed']);
            $table->index(['user_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
