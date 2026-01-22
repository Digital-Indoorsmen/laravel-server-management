<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Server extends Model
{
    use HasUlids, HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Server $server) {
            if (! $server->setup_token) {
                $server->setup_token = str()->random(32);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'setup_completed_at' => 'datetime',
        ];
    }

    public function sshKey(): BelongsTo
    {
        return $this->belongsTo(SshKey::class);
    }
}
