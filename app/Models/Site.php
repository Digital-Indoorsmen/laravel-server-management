<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory, HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'push_to_deploy' => 'boolean',
            'health_check_enabled' => 'boolean',
            'github_deployments_enabled' => 'boolean',
            'env_in_deploy_script' => 'boolean',
            'auto_cache_config' => 'boolean',
            'auto_restart_queue' => 'boolean',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
