<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseBackup extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['file_name', 'file_path', 'file_size', 'created_by_user_id', 'status', 'created_at'];

    protected function casts(): array
    {
        return ['file_size' => 'integer', 'created_at' => 'datetime'];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}