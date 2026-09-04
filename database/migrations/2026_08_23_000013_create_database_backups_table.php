<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_backups', function (Blueprint $table): void {
            $table->id();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('completed');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};