<?php

namespace App\Observers;

use App\Models\SelinuxAuditLog;
use Illuminate\Database\Eloquent\Model;

class ResourceObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->log($model, 'created', 'info');
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->log($model, 'updated', 'info');
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', 'info');
    }

    /**
     * Log the model event to selinux_audit_logs.
     */
    protected function log(Model $model, string $event, string $level): void
    {
        SelinuxAuditLog::create([
            'log_level' => $level,
            'message' => 'Model '.get_class($model)." was {$event}.",
            'context' => [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'event' => $event,
                'changes' => $event === 'updated' ? $model->getChanges() : null,
            ],
        ]);
    }
}
