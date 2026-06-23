<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Generic model audit logger. Attach any model in AppServiceProvider via
 * Model::observe(AuditObserver::class) and creates/updates/deletes are
 * recorded automatically — so new modules "just work". No-ops when there is
 * no authenticated actor (console / seeders).
 */
class AuditObserver
{
    public function created(Model $model): void { $this->log('created', $model); }
    public function updated(Model $model): void { $this->log('updated', $model); }
    public function deleted(Model $model): void { $this->log('deleted', $model); }

    private function log(string $action, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $module = class_basename($model);
        $label  = $model->name ?? $model->order_number ?? $model->title ?? ('#' . $model->getKey());

        AuditLog::record($action, $module, trim("{$module} {$label}"));
    }
}
