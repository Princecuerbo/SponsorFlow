<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropLegacyResidencyColumns();
        $this->relaxAddressColumns();
        $this->addSleFheStatusColumn();
    }

    private function dropLegacyResidencyColumns(): void
    {
        foreach (['residency', 'rurality'] as $column) {
            if (Schema::hasColumn('student_profiles', $column)) {
                Schema::table('student_profiles', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    private function relaxAddressColumns(): void
    {
        $addressColumns = [
            'province' => ['string', 150],
            'municipality' => ['string', 100],
            'barangay' => ['string', 150],
            'home_address' => ['text', null],
        ];

        foreach ($addressColumns as $column => [$type, $length]) {
            if (! Schema::hasColumn('student_profiles', $column)) {
                continue;
            }

            Schema::table('student_profiles', function (Blueprint $table) use ($column, $type, $length): void {
                $definition = $table->{$type}($column, $length);
                $definition->nullable()->change();
            });
        }
    }

    private function addSleFheStatusColumn(): void
    {
        if (Schema::hasColumn('student_profiles', 'sle_fhe_status')) {
            return;
        }

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->string('sle_fhe_status', 20)->default('Unverified')->after('is_sle_fhe_verified');
        });

        DB::table('student_profiles')
            ->where('is_sle_fhe_verified', true)
            ->where('sle_fhe_status', 'Unverified')
            ->update(['sle_fhe_status' => 'Verified']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_profiles', 'sle_fhe_status')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->dropColumn('sle_fhe_status');
            });
        }
    }
};
