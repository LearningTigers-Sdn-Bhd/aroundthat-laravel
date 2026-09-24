<?php

namespace App\Support\ActivityLog;

use App\Models\Activity;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Names the action behind model changes so the change log reads "rejected: …" instead of "updated".
 *
 * Model changes made inside as() are logged by the models themselves, with their field diff,
 * and receive this action's event name and reason. Use record() for an action that changes no fields.
 * Changes made inside content() also go to the content log, which admins review (see Activity::CONTENT_LOG).
 */
class AuditTrail
{
    protected ?string $event = null;

    protected ?string $reason = null;

    protected ?string $logName = null;

    /**
     * Run the callback so every change it logs is labelled with the event and reason.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function as(string $event, ?string $reason, Closure $callback): mixed
    {
        [$previousEvent, $previousReason] = [$this->event, $this->reason];
        [$this->event, $this->reason] = [$event, $reason];

        try {
            return $callback();
        } finally {
            [$this->event, $this->reason] = [$previousEvent, $previousReason];
        }
    }

    /**
     * Run the callback so every change it logs goes to the content log that admins review.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function content(Closure $callback): mixed
    {
        $previousLogName = $this->logName;
        $this->logName = Activity::CONTENT_LOG;

        try {
            return $callback();
        } finally {
            $this->logName = $previousLogName;
        }
    }

    /**
     * Run an owner's edit to public content: its changes get the event name and go to the content log.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function contentChange(string $event, Closure $callback): mixed
    {
        return $this->content(fn (): mixed => $this->as($event, null, $callback));
    }

    /**
     * Log an action that does not change the subject's fields.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(Model $subject, string $event, ?string $reason = null, array $properties = []): ?Activity
    {
        /** @var Activity|null */
        return $this->as($event, $reason, fn () => activity()
            ->performedOn($subject)
            ->event($event)
            ->withProperties($properties)
            ->log($event));
    }

    /**
     * Apply the current action to an activity that is about to be saved.
     */
    public function apply(Activity $activity): void
    {
        if ($this->logName !== null) {
            $activity->log_name = $this->logName;
        }

        if ($this->event === null) {
            return;
        }

        $activity->event = $this->event;
        $activity->description = $this->event;
        $activity->reason = $this->reason;
    }
}
