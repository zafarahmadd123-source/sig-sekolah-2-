<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasSchoolForeignKey = collect(Schema::getForeignKeys('users'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['sekolah_id']
                && $foreignKey['foreign_table'] === 'sekolahs'
                && $foreignKey['foreign_columns'] === ['id']);

        if (! $hasSchoolForeignKey) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreign('sekolah_id', 'users_sekolah_id_school_foreign')
                    ->references('id')
                    ->on('sekolahs')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasDeferredSchoolForeignKey = collect(Schema::getForeignKeys('users'))
            ->contains(
                fn (array $foreignKey): bool => $foreignKey['name'] === 'users_sekolah_id_school_foreign',
            );

        if ($hasDeferredSchoolForeignKey) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropForeign('users_sekolah_id_school_foreign');
            });
        }
    }
};
