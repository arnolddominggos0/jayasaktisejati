<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shipment_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $t->string('type', 80);         
            $t->timestamp('planned_at')->nullable();
            $t->timestamp('actual_at')->nullable();
            $t->string('location', 120)->nullable();
            $t->string('ref_no', 120)->nullable(); 
            $t->boolean('has_issue')->default(false);
            $t->string('issue_type', 40)->nullable(); 
            $t->text('remarks')->nullable();
            $t->json('data')->nullable();    
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('shipment_events');
    }
};
