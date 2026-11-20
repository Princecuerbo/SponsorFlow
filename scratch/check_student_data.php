<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'student@sponsorflow.test')->with('studentProfile')->first();
echo json_encode([
    'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'first_name' => $user->first_name ?? null,
        'last_name' => $user->last_name ?? null,
    ],
    'student_profile' => $user->studentProfile?->toArray(),
], JSON_PRETTY_PRINT);
