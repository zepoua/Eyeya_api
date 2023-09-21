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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom_entreprise')->nullable();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('adresse');
            $table->geometry('position')->nullable();
            $table->bigInteger('telephone1')->unique();
            $table->bigInteger('telephone2')->unique()->nullable();
            $table->string('qualification');
            $table->mediumText('experience');
            $table->mediumText('description');
            $table->string('image1')->nullable();
            $table->string('image2')->nullable();
            $table->string('image3')->nullable();
            $table->foreignId('domaine_id')->constrained();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::table('notations', function (Blueprint $table){
            $table->dropForeign(['domaine_id']);
        });

        Schema::dropIfExists('users');
    }
};
