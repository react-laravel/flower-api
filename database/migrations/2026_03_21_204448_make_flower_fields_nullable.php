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
        Schema::table('flowers', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->string('meaning')->nullable()->change();
            $table->text('care')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flowers', function (Blueprint $table) {
            $table->string('image')->nullable(false)->change();
            $table->text('description')->nullable(false)->change();
            $table->string('meaning')->nullable(false)->change();
            $table->text('care')->nullable(false)->change();
        });
    }
};
