<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SLE-FHE masterlist source
    |--------------------------------------------------------------------------
    |
    | Absolute path to the official "SLE-FHE DATA.xlsx" workbook used to seed the
    | master registry. Leave null to use the conventional locations: storage/app
    | first, then the repository root. Set this to an explicit path to override
    | both (useful when the workbook is kept on a network share).
    |
    */

    'registry_source' => env('SLE_FHE_REGISTRY_SOURCE'),

];
