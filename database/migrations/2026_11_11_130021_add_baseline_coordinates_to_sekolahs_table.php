<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sekolahs', function (Blueprint $table) {
            $table->decimal('baseline_latitude', 10, 7)->nullable();
            $table->decimal('baseline_longitude', 10, 7)->nullable();
        });

        DB::table('sekolahs')->whereNull('baseline_latitude')->update([
            'baseline_latitude' => DB::raw('latitude'),
            'baseline_longitude' => DB::raw('longitude'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sekolahs', function (Blueprint $table) {
            $table->dropColumn(['baseline_latitude', 'baseline_longitude']);
        });
    }
};
