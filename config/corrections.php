<?php

return [
    // Enrol independently verified PUBLIC keys through a separately reviewed deployment.
    // Each entry: key ID => ['authority' => '...', 'pem' => '-----BEGIN PUBLIC KEY-----...'].
    'approval_keys' => [],
    'execution_keys' => [],
    'production_apply_enabled' => false,
    'protected_databases' => ['sponsorflow'],
];
