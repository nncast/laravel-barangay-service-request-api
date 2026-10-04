<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting a staff or admin account used to cascade-delete every status log
 * they wrote, wiping the history of other residents' requests. Keep the logs
 * and clear the author instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_logs', function (Blueprint $table) {
            $table->dropForeign(['changed_by']);
        });

        Schema::table('status_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('changed_by')->nullable()->change();
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('status_logs', function (Blueprint $table) {
            $table->dropForeign(['changed_by']);
        });

        Schema::table('status_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('changed_by')->nullable(false)->change();
            $table->foreign('changed_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
