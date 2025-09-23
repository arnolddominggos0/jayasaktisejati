<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('armada_assignments', function (Blueprint $table) {
            try {
                $table->dropUnique(['armada_id','date']);
            } catch (\Throwable $e) {}

            if (!Schema::hasColumn('armada_assignments', 'manpower_id')) {
                $table->foreignId('manpower_id')->nullable()->constrained('manpowers')->nullOnDelete();
            }
            if (!Schema::hasColumn('armada_assignments', 'role')) {
                $table->string('role')->nullable();
            }
            if (!Schema::hasColumn('armada_assignments', 'status')) {
                $table->enum('status', ['draft','active','finished','canceled'])->default('draft');
            }
            if (!Schema::hasColumn('armada_assignments', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }
            if (!Schema::hasColumn('armada_assignments', 'ended_at')) {
                $table->timestamp('ended_at')->nullable();
            }
            if (!Schema::hasColumn('armada_assignments', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('armada_assignments', 'depot_id')) {
                $table->foreignId('depot_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('armada_assignments', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('armada_assignments', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('armada_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'manpower_id','role','status','started_at','ended_at',
                'branch_id','depot_id','created_by','updated_by'
            ]);
        });
    }
};
