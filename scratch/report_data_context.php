<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('email', 'student@sponsorflow.test')->first();
$p = $user->studentProfile;
echo "USER name: " . $user->name . PHP_EOL;
echo "USER first_name: " . var_export($user->first_name, true) . PHP_EOL;
echo "PROFILE names: " . json_encode([
    'first_name' => $p->first_name,
    'middle_name' => $p->middle_name,
    'last_name' => $p->last_name,
    'extension_name' => $p->extension_name,
    'campus' => $p->campus,
    'sle_fhe_status' => $p->sle_fhe_status,
], JSON_UNESCAPED_UNICODE) . PHP_EOL;

foreach ($p->applications()->with('sponsorshipProgram.sponsor')->get() as $a) {
    echo "APP {$a->id} | status=" . (is_object($a->status) ? $a->status->value : $a->status)
        . " | program=" . ($a->sponsorshipProgram->program_name ?? 'n/a')
        . " | submitted=" . ($a->submitted_at?->format('Y-m-d H:i') ?? 'n/a')
        . " | docs=" . $a->documents()->count() . PHP_EOL;
}

$counts = [];
foreach (App\Models\Application::select('status')->get() as $row) {
    $v = is_object($row->status) ? $row->status->value : (string) $row->status;
    $counts[$v] = ($counts[$v] ?? 0) + 1;
}
ksort($counts);
echo "APP STATUS COUNTS: " . json_encode($counts) . PHP_EOL;
