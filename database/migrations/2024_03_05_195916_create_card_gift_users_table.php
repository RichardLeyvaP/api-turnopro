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
        Schema::create('card_gift_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_gift_id');
            $table->unsignedBigInteger('user_id');            
            $table->string('state');
            $table->string('code');
            $table->date('issue_date');
            $table->date('expiration_date');
            $table->double('exist')->nullable();
            $table->foreign('card_gift_id')->references('id')->on('card_gifts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_gift_users');
    }
};
