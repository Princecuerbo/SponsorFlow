<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\User::where('role', 'student')->get() as $user) {
    $profile = $user->studentProfile;
    if (! $profile) {
        continue;
    }
    echo $user->email . ' | ' . $profile->sle_fhe_status . ' | apps=' . $profile->applications()->count() . PHP_EOL;
}
