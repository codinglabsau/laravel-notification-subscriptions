<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('notification_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('type');
            $table->json('channels');
            $table->timestamps();

            $table->unique(['user_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_subscriptions');
    }
};
