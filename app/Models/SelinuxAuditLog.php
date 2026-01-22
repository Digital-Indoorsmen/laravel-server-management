<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SelinuxAuditLog extends Model
{
    use HasUlids;

    protected $guarded = [];

    public const UPDATED_AT = null;

    protected $casts = [
        'context' => 'json',
    ];
}
