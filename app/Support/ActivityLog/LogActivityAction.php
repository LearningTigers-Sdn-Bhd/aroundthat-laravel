<?php

namespace App\Support\ActivityLog;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Actions\LogActivityAction as BaseLogActivityAction;

/**
 * Adds the current action, reason and request IP to every activity before it is saved.
 * A content change an admin makes is marked reviewed at once, so the change feed only shows owners' edits.
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

        if ($activity->log_name === Activity::CONTENT_LOG && $activity->causer instanceof User && $activity->causer->is_admin) {
            $activity->reviewed_at ??= now();
            $activity->reviewed_by_id ??= $activity->causer->id;
        }

        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            $activity->ip_address ??= request()->ip();
        }
    }
}
