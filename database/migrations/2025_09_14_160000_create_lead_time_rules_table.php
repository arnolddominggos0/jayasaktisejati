<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('lead_time_rules')) {
            Schema::create('lead_time_rules', function (Blueprint $table) {
                $table->id();
                $table->string('mode_code', 8);             
                $table->string('service_option', 32)->nullable(); 
                $table->string('priority', 16)->default('normal'); 
                $table->integer('plus_days')->default(0);        
                $table->boolean('end_of_day')->default(true);     
                $table->timestamps();

                $table->index(['mode_code','service_option','priority'], 'lead_time_rules_key_idx');
            });

            $rows = [
                ['SH', null, 'normal', 19, true],
                ['SH', null, 'urgent', 17, true],
                ['TC', null, 'normal', 1,  true],
                ['TC', null, 'urgent', 0,  true],
            ];

            foreach ($rows as [$mode, $opt, $prio, $days, $eod]) {
                $exists = DB::table('lead_time_rules')
                    ->where('mode_code', $mode)
                    ->whereNull('service_option')
                    ->where('priority', $prio)
                    ->exists();

                if (! $exists) {
                    DB::table('lead_time_rules')->insert([
                        'mode_code'      => $mode,
                        'service_option' => $opt,
                        'priority'       => $prio,
                        'plus_days'      => $days,
                        'end_of_day'     => $eod,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_time_rules');
    }
};
