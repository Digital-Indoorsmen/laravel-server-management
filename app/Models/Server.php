<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Server extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function sshKey(): BelongsTo
    {
        return $this->belongsTo(SshKey::class);
    }
}
