<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasUlids;

    protected $guarded = [];

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
