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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'country')) {
                $table->dropColumn('country');
            }
            if (!Schema::hasColumn('users', 'country_en')) {
                $table->string('country_en')->nullable()->after('age');
            }
            if (!Schema::hasColumn('users', 'country_ar')) {
                $table->string('country_ar')->nullable()->after('age');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'country_en')) {
                $table->renameColumn('country_en', 'country');
            }
            $table->dropColumn('country_ar');
        });
    }
};
