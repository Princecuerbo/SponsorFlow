<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $items = collect();

        switch ($request->query('type')) {
            case 'provinces':
                $items = DB::table('localaddress')
                    ->distinct()
                    ->orderBy('province')
                    ->pluck('province');
                break;

            case 'municipalities':
                $province = trim((string) $request->query('province', ''));

                if ($province !== '') {
                    $items = DB::table('localaddress')
                        ->where('province', $province)
                        ->orderBy('city')
                        ->pluck('city');
                }
                break;
        }

        return response()->json($items);
    }
}