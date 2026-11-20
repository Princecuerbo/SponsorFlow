<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = App\Models\User::where('email', 'student@sponsorflow.test')->first();
echo "Student ID: " . $student?->id . ", Name: " . $student?->name . PHP_EOL;
$profile = $student?->studentProfile;
echo "Profile SLE Status: " . $profile?->sle_fhe_status . PHP_EOL;

$programs = App\Models\SponsorshipProgram::all();
echo "Total programs: " . $programs->count() . PHP_EOL;
foreach ($programs as $p) {
    echo "ID: {$p->id} | {$p->program_name} | {$p->category->value} | Status: {$p->status->value} | Slots: {$p->available_slots}" . PHP_EOL;
}
