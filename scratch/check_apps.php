<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'student@sponsorflow.test')->with('studentProfile.applications.sponsorshipProgram.sponsor', 'studentProfile.applications.documents')->first();
$apps = $user->studentProfile?->applications ?? collect();

echo "Applications count: " . $apps->count() . "\n";
foreach ($apps as $a) {
    echo "ID: {$a->id}, Program: {$a->sponsorshipProgram?->program_name}, Status: {$a->status->value}, Docs count: {$a->documents->count()}\n";
}

$allApps = App\Models\Application::with('sponsorshipProgram.sponsor', 'documents')->get();
echo "\nTotal applications in system: " . $allApps->count() . "\n";
foreach ($allApps as $a) {
    echo "ID: {$a->id}, StudentProfileID: {$a->student_profile_id}, Program: {$a->sponsorshipProgram?->program_name}, Status: {$a->status->value}, Docs: {$a->documents->count()}\n";
}

$emptyStudents = App\Models\User::where('role', 'student')->whereDoesntHave('studentProfile.applications')->get(['id', 'name', 'email']);
echo "\nStudents with 0 applications:\n";
foreach ($emptyStudents as $s) {
    echo "ID: {$s->id}, Name: {$s->name}, Email: {$s->email}\n";
}

$a7 = App\Models\Application::with('documents', 'sponsorshipProgram.sponsor', 'studentProfile')->find(7);
echo "\nApplication 7 details:\n" . json_encode($a7->toArray(), JSON_PRETTY_PRINT) . "\n";


