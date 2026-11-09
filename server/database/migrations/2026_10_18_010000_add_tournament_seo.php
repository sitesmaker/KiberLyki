<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('seoTitle', 200)->nullable();
            $table->string('seoDescription', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', fn (Blueprint $table) => $table->dropColumn(['seoTitle', 'seoDescription']));
    }
};
