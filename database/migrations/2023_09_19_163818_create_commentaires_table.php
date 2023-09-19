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
        Schema::create('commentaires', function (Blueprint $table) {
            $table->id();
            $table->mediumText('commentaire_lib');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('client_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::table('notations', function (Blueprint $table){
            $table->dropForeign(['user_id']);
            $table->dropForeign(['client_id']);
        });

        Schema::dropIfExists('commentaires');
    }
};
