<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('converts', function (Blueprint $table) {
            $table->id();
            $table->foreignId( 'user' )->nullable()->references( 'id' )->on( 'users' );
            $table->string( 'directory' );
            $table->json( 'resolutions' );
            $table->dateTime( 'expires' );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('converts');
    }
};
