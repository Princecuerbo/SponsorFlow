<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Before dropping the redundant `slots` column, sync any records whose
        // `total_slots` was never populated but have a value in `slots`, so no
        // quota data is lost. `total_slots` is the source of truth going forward.
        if (Schema::hasColumn('sponsorship_programs', 'slots')) {
            DB::table('sponsorship_programs')
                ->where('total_slots', 0)
                ->where('slots', '>', 0)
                ->update(['total_slots' => DB::raw('slots')]);

            Schema::table('sponsorship_programs', function (Blueprint $table) {
                $table->dropColumn('slots');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table) {
            if (!Schema::hasColumn('sponsorship_programs', 'slots')) {
                $table->integer('slots')->default(0);
            }
        });
    }
};
