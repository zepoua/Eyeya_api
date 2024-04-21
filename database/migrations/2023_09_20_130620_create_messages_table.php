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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->unsignedBigInteger('id_exp');
            $table->string('exp_type');
            $table->unsignedBigInteger('id_dest');
            $table->string('dest_type');
            $table->date('date_envoi');
            $table->date('read_at')->nullable();

            // Clés étrangères pour les clients
            $table->foreign('id_exp')->references('id')->on('clients');
            $table->foreign('id_dest')->references('id')->on('clients');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
