<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\SponsorshipProgram::all() as $p) {
    if (!empty($p->required_documents)) {
        echo "Program {$p->id}: " . json_encode($p->required_documents) . PHP_EOL;
    }
}
