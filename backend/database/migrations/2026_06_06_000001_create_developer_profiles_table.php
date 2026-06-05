<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('developer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('username', 64)->unique();
            $table->string('headline', 255)->nullable();
            $table->text('bio')->nullable();
            $table->string('github_username', 255)->nullable();
            $table->string('website_url', 512)->nullable();
            $table->string('location', 255)->nullable();
            $table->boolean('is_public')->default(false);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('developer_profiles');
    }
};
