<?php

namespace App\Support\ActivityLog;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Actions\LogActivityAction as BaseLogActivityAction;

/**
 * Adds the current action, reason and request IP to every activity before it is saved.
 */
class LogActivityAction extends BaseLogActivityAction
{
    protected function beforeActivityLogged(Model $activity): void
    {
        parent::beforeActivityLogged($activity);

        if (! $activity instanceof Activity) {
            return;
        }

        app(AuditTrail::class)->apply($activity);

        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            $activity->ip_address ??= request()->ip();
        }
    }
}
