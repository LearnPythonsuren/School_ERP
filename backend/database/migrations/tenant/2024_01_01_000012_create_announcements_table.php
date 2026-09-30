<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->enum('channel', ['sms', 'email', 'push'])->default('push');
            $table->enum('audience', ['all', 'parents', 'staff', 'class'])->default('all');
            $table->unsignedInteger('recipients')->default(0);
            $table->enum('status', ['sent', 'draft'])->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
