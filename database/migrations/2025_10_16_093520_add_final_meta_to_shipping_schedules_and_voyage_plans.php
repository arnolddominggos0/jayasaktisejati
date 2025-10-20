<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shipping_schedules')) {
            Schema::create('shipping_schedules', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('customer_id')->nullable();
                $t->unsignedBigInteger('pol_id')->nullable();
                $t->unsignedBigInteger('pod_id')->nullable();
                $t->string('period_ym', 7);
                $t->string('state', 10)->default('draft');
                $t->string('title')->nullable();
                $t->text('notes')->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamp('finalized_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('shipping_schedule_items')) {
            Schema::create('shipping_schedule_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('schedule_id');
                $t->unsignedBigInteger('shipping_line_id')->nullable();
                $t->unsignedBigInteger('vessel_id')->nullable();
                $t->string('voyage_no', 50)->nullable();
                $t->unsignedBigInteger('pol_id')->nullable();
                $t->unsignedBigInteger('pod_id')->nullable();
                $t->timestamp('etd')->nullable();
                $t->timestamp('eta')->nullable();
                $t->string('service', 50)->nullable();
                $t->json('extra')->nullable();
                $t->timestamps();
                $t->foreign('schedule_id')->references('id')->on('shipping_schedules')->cascadeOnDelete();
            });
        }

        Schema::table('shipping_schedules', function (Blueprint $t) {
            if (!Schema::hasColumn('shipping_schedules', 'final_source')) $t->string('final_source', 30)->nullable();
            if (!Schema::hasColumn('shipping_schedules', 'final_attachment')) $t->string('final_attachment')->nullable();
            if (!Schema::hasColumn('shipping_schedules', 'final_note')) $t->text('final_note')->nullable();
            if (!Schema::hasColumn('shipping_schedules', 'approved_by_name')) $t->string('approved_by_name', 120)->nullable();
            if (!Schema::hasColumn('shipping_schedules', 'approved_at')) $t->timestamp('approved_at')->nullable();
        });

        if (Schema::hasTable('voyage_plans') && !Schema::hasColumn('voyage_plans', 'approval_ref')) {
            Schema::table('voyage_plans', function (Blueprint $t) {
                $t->string('approval_ref')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('voyage_plans') && Schema::hasColumn('voyage_plans', 'approval_ref')) {
            Schema::table('voyage_plans', function (Blueprint $t) {
                $t->dropColumn('approval_ref');
            });
        }
        if (Schema::hasTable('shipping_schedules')) {
            Schema::table('shipping_schedules', function (Blueprint $t) {
                if (Schema::hasColumn('shipping_schedules', 'final_source')) $t->dropColumn('final_source');
                if (Schema::hasColumn('shipping_schedules', 'final_attachment')) $t->dropColumn('final_attachment');
                if (Schema::hasColumn('shipping_schedules', 'final_note')) $t->dropColumn('final_note');
                if (Schema::hasColumn('shipping_schedules', 'approved_by_name')) $t->dropColumn('approved_by_name');
                if (Schema::hasColumn('shipping_schedules', 'approved_at')) $t->dropColumn('approved_at');
            });
        }
    }
};
