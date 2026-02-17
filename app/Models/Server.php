<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Server extends Model
{
    use HasFactory, HasUlids;

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
            'database_engines' => 'array',
            'software' => 'array',
        ];
    }

    public function sshKey(): BelongsTo
    {
        return $this->belongsTo(SshKey::class);
    }

    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ServerLog::class);
    }

    public function sites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function softwareInstallations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SoftwareInstallation::class);
    }
}
