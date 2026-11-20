<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = App\Models\User::where('email', 'student@sponsorflow.test')->first();
$p6 = App\Models\SponsorshipProgram::find(6);
echo "Program 6: {$p6->program_name}" . PHP_EOL;
echo "Student has active sponsorship: " . ($student->studentProfile->hasActiveSponsorship() ? 'YES' : 'NO') . PHP_EOL;
echo "Program 6 has active app for student: " . ($p6->hasActiveApplicationForStudent($student->studentProfile->id) ? 'YES' : 'NO') . PHP_EOL;
echo "Campus restricted: " . (empty($p6->eligible_campuses) ? 'NO' : json_encode($p6->eligible_campuses)) . PHP_EOL;
