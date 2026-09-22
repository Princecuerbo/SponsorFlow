<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropLegacyResidencyColumns();
        $this->dropRedundantSleFheStatusColumn();
        $this->softenAddressColumns();
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

    /**
     * SLE-FHE status is now derived from the dedicated sle_fhe_requests,
     * sle_fhe_verifications, and sle_fhe_rejections tables via
     * StudentProfile::getSleFheStatusAttribute(), so the stored column is redundant.
     */
    private function dropRedundantSleFheStatusColumn(): void
    {
        if (Schema::hasColumn('student_profiles', 'sle_fhe_status')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->dropColumn('sle_fhe_status');
            });
        }
    }

    /**
     * Registration no longer asks for a residence, so address fields stay nullable.
     */
    private function softenAddressColumns(): void
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

    public function down(): void
    {
        if (! Schema::hasColumn('student_profiles', 'sle_fhe_status')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->string('sle_fhe_status', 20)->default('Unverified')->after('is_sle_fhe_verified');
            });
        }
    }
};
