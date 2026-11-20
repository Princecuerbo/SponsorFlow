<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$emails = [
    'juana.hills@example.org',
    'nathangarcia@dorsu.edu.ph',
    'princelinebert.cuerbo@dorsu.edu.ph',
    'mikeefault@dorsu.edu.ph',
];
$candidates = ['password123', 'password', 'Password123!', 'student123', 'SponsorFlow123'];

foreach ($emails as $email) {
    $user = App\Models\User::where('email', $email)->first();
    if (! $user) {
        echo $email . " | NOT FOUND\n";
        continue;
    }
    $matches = [];
    foreach ($candidates as $candidate) {
        if (Illuminate\Support\Facades\Hash::check($candidate, $user->password)) {
            $matches[] = $candidate;
        }
    }
    echo $email . ' | matches: ' . (implode(', ', $matches) ?: 'none') . PHP_EOL;
}
