<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drip_campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drip_campaign_id')->constrained('drip_campaigns')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->unsignedInteger('delay_hours')->default(0);
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->foreignId('message_segment_id')->nullable()->constrained('message_segments')->nullOnDelete();
            $table->timestamps();

            $table->index('drip_campaign_id');
            $table->unique(['drip_campaign_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_campaign_steps');
    }
};
