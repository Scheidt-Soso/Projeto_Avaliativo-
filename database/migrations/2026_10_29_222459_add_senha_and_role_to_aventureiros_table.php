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
        Schema::table('aventureiros', function (Blueprint $table) {
        $table->string('senha')->nullable();
        $table->enum('role', ['jogador', 'admin'])->default('jogador');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aventureiros', function (Blueprint $table) {
        $table->dropColumn(['senha', 'role']);
    });
    }
};
