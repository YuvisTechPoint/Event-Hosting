<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('community_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_user_id')->constrained('users')->onDelete('cascade');
            $table->string('target_type', 50);
            $table->unsignedBigInteger('target_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['follower_user_id', 'target_type', 'target_id']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_follows');
    }
};
