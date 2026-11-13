<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->decimal('gpa_submitted', 3, 2)->nullable()->change();
            $table->string('address_submitted', 255)->nullable()->change();
            $table->boolean('is_rural_submitted')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Unknown qualifications cannot safely be converted to invented non-null values.');
    }
};
