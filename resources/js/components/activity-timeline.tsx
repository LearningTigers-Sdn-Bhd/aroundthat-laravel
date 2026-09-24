import { ArrowRight } from 'lucide-react';
import { Skeleton } from '@/components/ui/skeleton';
import { formatDateTime, humanize } from '@/lib/format';

const isoDateTime = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/;

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (Array.isArray(value)) {
        return value.length > 0 ? value.map(formatValue).join(', ') : '—';
    }

    if (typeof value === 'string') {
        return isoDateTime.test(value) ? formatDateTime(value) : value;
    }

    if (typeof value === 'number') {
        return value.toLocaleString();
    }

    return JSON.stringify(value);
}

/**
 * A change history, newest first as given: what happened, who did it, why, and each field's old → new value.
 */
export default function ActivityTimeline({
    activities,
}: {
    activities: App.Data.Admin.ActivityData[];
}) {
    if (activities.length === 0) {
        return <p className="text-sm text-muted-foreground">No changes yet.</p>;
    }

    return (
        <ol className="relative space-y-6 border-l pl-6">
            {activities.map((activity) => (
                <li key={activity.id} className="relative">
                    <span className="absolute top-1.5 -left-[29px] size-2.5 rounded-full border-2 border-background bg-muted-foreground" />

                    <div className="flex flex-wrap items-baseline gap-x-2 text-sm">
                        <span className="font-medium">
                            {humanize(activity.event)}
                        </span>
                        <span className="text-muted-foreground">
                            by {activity.causer_name ?? 'System'}
                        </span>
                        {activity.created_at && (
                            <time
                                dateTime={activity.created_at}
                                className="text-xs text-muted-foreground"
                            >
                                {formatDateTime(activity.created_at)}
                            </time>
                        )}
                    </div>

                    {activity.reason && (
                        <p className="mt-1 text-sm">
                            <span className="text-muted-foreground">
                                Reason:{' '}
                            </span>
                            {activity.reason}
                        </p>
                    )}

                    {activity.changes.length > 0 && (
                        <dl className="mt-2 grid gap-1 text-sm">
                            {activity.changes.map((change) => (
                                <div
                                    key={change.field}
                                    className="flex flex-wrap items-center gap-x-2"
                                >
                                    <dt className="text-muted-foreground">
                                        {humanize(change.field)}
                                    </dt>
                                    <dd className="flex flex-wrap items-center gap-x-2">
                                        <span className="text-muted-foreground line-through">
                                            {formatValue(change.old)}
                                        </span>
                                        <ArrowRight className="size-3 text-muted-foreground" />
                                        <span>{formatValue(change.new)}</span>
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    )}

                    {Object.keys(activity.properties).length > 0 && (
                        <dl className="mt-2 grid gap-1 text-sm">
                            {Object.entries(activity.properties).map(
                                ([key, value]) => (
                                    <div key={key} className="flex gap-x-2">
                                        <dt className="text-muted-foreground">
                                            {humanize(key)}
                                        </dt>
                                        <dd>{formatValue(value)}</dd>
                                    </div>
                                ),
                            )}
                        </dl>
                    )}
                </li>
            ))}
        </ol>
    );
}

/**
 * Shown while a deferred activity prop loads.
 */
export function ActivitySkeleton() {
    return (
        <div className="space-y-4">
            {[0, 1, 2].map((row) => (
                <div key={row} className="space-y-2">
                    <Skeleton className="h-4 w-1/3" />
                    <Skeleton className="h-4 w-2/3" />
                </div>
            ))}
        </div>
    );
}
