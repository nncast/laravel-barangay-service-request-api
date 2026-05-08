<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add our custom columns
            $table->string('full_name')->after('id');
            $table->string('phone', 20)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('profile_photo')->nullable()->after('address');
            $table->enum('role', ['resident', 'staff', 'admin'])->default('resident')->after('profile_photo');
            $table->boolean('is_verified')->default(false)->after('role');
            $table->boolean('is_active')->default(true)->after('is_verified');
            
            // Make email nullable since we have full_name as required identifier
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'phone', 'address', 'profile_photo', 'role', 'is_verified', 'is_active']);
            $table->string('email')->nullable(false)->change();
        });
    }
};