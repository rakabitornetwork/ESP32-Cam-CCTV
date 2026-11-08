<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captures', function (Blueprint $table) {
            $table->unsignedSmallInteger('rotation')->default(0)->after('captured_at');
        });
    }

    public function down(): void
    {
        Schema::table('captures', function (Blueprint $table) {
            $table->dropColumn('rotation');
        });
    }
};
