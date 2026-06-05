<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_questions_event_id ON questions (event_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_order_items_product_id_product_price_id ON order_items (product_id, product_price_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_promo_codes_event_id_code ON promo_codes (event_id, code)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_events_organizer_id_status ON events (organizer_id, status)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_questions_event_id');
        DB::statement('DROP INDEX IF EXISTS idx_order_items_product_id_product_price_id');
        DB::statement('DROP INDEX IF EXISTS idx_promo_codes_event_id_code');
        DB::statement('DROP INDEX IF EXISTS idx_events_organizer_id_status');
    }
};
