<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id('program_id');
            $table->string('code', 50)->index();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->boolean('is_board_program')->default(false);
            $table->boolean('is_undergraduate')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_programs');
    }
};
