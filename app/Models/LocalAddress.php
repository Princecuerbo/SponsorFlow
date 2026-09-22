<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalAddress extends Model
{
    use HasFactory;

    protected $table = 'localaddress';

    protected $primaryKey = 'localaddressid';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'province',
        'city',
        'latlong',
        'latitude',
        'longitude',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
