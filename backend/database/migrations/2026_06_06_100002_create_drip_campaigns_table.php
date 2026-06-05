<?php

use HiEvents\DomainObjects\Enums\DripCampaignTrigger;
use HiEvents\DomainObjects\Status\DripCampaignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drip_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->enum('trigger', DripCampaignTrigger::valuesArray());
            $table->enum('status', DripCampaignStatus::valuesArray())->default(DripCampaignStatus::DRAFT->value);
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->index('event_id');
            $table->index(['event_id', 'status']);
            $table->index('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_campaigns');
    }
};
