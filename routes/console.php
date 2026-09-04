<?php

use App\Enums\UserRole;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sponsorflow:repair-sponsor-profiles {--dry-run : Report missing profiles without creating them}', function () {
    $missingProfiles = User::query()
        ->where('role', UserRole::Sponsor)
        ->whereDoesntHave('sponsor')
        ->get();

    if ($missingProfiles->isEmpty()) {
        $this->info('All sponsor users already have linked profiles.');

        return 0;
    }

    if ($this->option('dry-run')) {
        $this->warn("{$missingProfiles->count()} missing sponsor profile(s) found. No changes made.");

        return 0;
    }

    foreach ($missingProfiles as $user) {
        Sponsor::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'company_organization_name' => $user->name,
                'contact_person' => $user->name,
                'contact_email' => $user->email,
            ],
        );
    }

    $this->info("Created {$missingProfiles->count()} sponsor profile(s).");

    return 0;
})->purpose('Create missing sponsor profiles for sponsor users');
