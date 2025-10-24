<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            $table->boolean('has_issue')->default(false)->after('location');
            $table->string('issue_type')->nullable()->after('has_issue'); 
            $table->text('issue_note')->nullable()->after('issue_type');
            $table->json('issue_photos')->nullable()->after('issue_note');
        });
    }
    public function down(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            $table->dropColumn(['has_issue', 'issue_type', 'issue_note', 'issue_photos']);
        });
    }
};
