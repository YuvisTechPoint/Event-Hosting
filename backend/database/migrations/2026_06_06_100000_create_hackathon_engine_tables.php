<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hackathon_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->text('description')->nullable();
            $table->string('invite_code', 32)->unique();
            $table->unsignedSmallInteger('max_members')->default(4);
            $table->string('status', 50)->default('OPEN');
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'slug']);
            $table->index(['event_id', 'status']);
        });

        Schema::create('hackathon_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('hackathon_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 255);
            $table->string('first_name', 255);
            $table->string('last_name', 255)->nullable();
            $table->string('role', 50)->default('MEMBER');
            $table->string('status', 50)->default('ACTIVE');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'email']);
            $table->index('user_id');
        });

        Schema::create('hackathon_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('team_id')->unique()->constrained('hackathon_teams')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->text('description')->nullable();
            $table->string('repository_url', 500)->nullable();
            $table->string('demo_url', 500)->nullable();
            $table->jsonb('tech_stack')->nullable();
            $table->string('status', 50)->default('DRAFT');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'slug']);
            $table->index(['event_id', 'status']);
        });

        Schema::create('hackathon_judging_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 50)->default('DRAFT');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        Schema::create('hackathon_judging_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('judging_round_id')->nullable()->constrained('hackathon_judging_rounds')->nullOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('max_score')->default(10);
            $table->unsignedSmallInteger('weight')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'judging_round_id']);
        });

        Schema::create('hackathon_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('hackathon_projects')->cascadeOnDelete();
            $table->foreignId('judging_round_id')->constrained('hackathon_judging_rounds')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('SUBMITTED');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'judging_round_id']);
        });

        Schema::create('hackathon_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_id')->constrained('hackathon_judging_criteria')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('hackathon_projects')->cascadeOnDelete();
            $table->foreignId('judge_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['criteria_id', 'project_id', 'judge_user_id']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hackathon_scores');
        Schema::dropIfExists('hackathon_submissions');
        Schema::dropIfExists('hackathon_judging_criteria');
        Schema::dropIfExists('hackathon_judging_rounds');
        Schema::dropIfExists('hackathon_projects');
        Schema::dropIfExists('hackathon_team_members');
        Schema::dropIfExists('hackathon_teams');
    }
};
