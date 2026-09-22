<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resident address fields moved to the sle_fhe_requests / sle_fhe_verifications tables,
     * and SLE-FHE verification is now derived from sle_fhe_verifications rows.
     */
    public function up(): void
    {
        $columns = [
            'province',
            'municipality',
            'barangay',
            'home_address',
            'is_rural',
            'is_sle_fhe_verified',
            'sle_fhe_cg_path',
            'sle_fhe_residence_path',
            'sle_fhe_barangay_path',
        ];

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            foreach (Schema::getIndexes('student_profiles') as $index) {
                $indexColumns = $index['columns'] ?? [];
                $indexName = $index['name'] ?? null;
                if ($indexName !== null && count(array_intersect($indexColumns, $columns)) > 0 && ! ($index['primary'] ?? false)) {
                    Schema::table('student_profiles', function (Blueprint $table) use ($indexName): void {
                        $table->dropIndex($indexName);
                    });
                }
            }
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn('student_profiles', $column)) {
                Schema::table('student_profiles', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('student_profiles', 'barangay')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->string('barangay', 150)->nullable()->after('address');
                $table->index('barangay');
            });
        }
        if (! Schema::hasColumn('student_profiles', 'province')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->string('province', 150)->nullable();
            });
        }
        if (! Schema::hasColumn('student_profiles', 'municipality')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->string('municipality', 100)->nullable();
            });
        }
        if (! Schema::hasColumn('student_profiles', 'home_address')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->text('home_address')->nullable();
            });
        }
        if (! Schema::hasColumn('student_profiles', 'is_rural')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->boolean('is_rural')->default(false);
                $table->index('is_rural');
            });
        }
        if (! Schema::hasColumn('student_profiles', 'is_sle_fhe_verified')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->boolean('is_sle_fhe_verified')->default(false);
                $table->index('is_sle_fhe_verified');
            });
        }
        if (! Schema::hasColumn('student_profiles', 'sle_fhe_cg_path')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->string('sle_fhe_cg_path')->nullable();
            });
        }
        if (! Schema::hasColumn('student_profiles', 'sle_fhe_residence_path')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->string('sle_fhe_residence_path')->nullable();
            });
        }
        if (! Schema::hasColumn('student_profiles', 'sle_fhe_barangay_path')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->string('sle_fhe_barangay_path')->nullable();
            });
        }
    }
};
