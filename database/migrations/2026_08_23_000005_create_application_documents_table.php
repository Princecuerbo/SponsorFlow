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
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();
            $table->string('document_type', 100);
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->timestamps();

            $table->index('document_type');
            $table->index(['application_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
