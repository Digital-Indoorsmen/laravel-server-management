<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SshKey extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'private_key' => 'encrypted',
    ];
}
