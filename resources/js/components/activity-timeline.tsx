import { ArrowRight, History } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Skeleton } from '@/components/ui/skeleton';
import { formatDateTime, humanize } from '@/lib/format';

const isoDateTime = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/;

const fieldLabels: Record<string, string> = {
    google_maps_url: 'Google Maps link',
    regular_hours: 'Weekly hours',
    is_listed: 'Listed publicly',
    date_exceptions: 'Special dates',
    whatsapp: 'WhatsApp',
    reverts: 'Undid',
};

const eventLabels: Record<string, string> = {
    details_changed: 'Details changed',
    public_profile_changed: 'Public page changed',
    hours_changed: 'Hours changed',
    date_exceptions_changed: 'Special dates changed',
    category_changed: 'Category changed',
    tags_changed: 'Tags changed',
    image_added: 'Photo added',
    image_changed: 'Photo description changed',
    image_removed: 'Photo removed',
    images_reordered: 'Photos reordered',
    reverted: 'Reverted by an admin',
    activated: 'Activated',
    paused: 'Paused',
    outlets_changed: 'Outlets changed',
    sponsored_outlet_added: 'Sponsored outlet added',
    sponsored_outlet_removed: 'Sponsored outlet removed',
    redemption_refused: 'Voucher refused at the counter',
};

/**
 * How an activity's event reads, such as "Public page changed".
 */
export function eventLabel(event: string): string {
    return eventLabels[event] ?? humanize(event);
}

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function fieldLabel(field: string): string {
    return fieldLabels[field] ?? humanize(field);
}

/**
 * Weekly hours as logged: `{ "1": [{ opens, closes }], … }`, keyed by ISO weekday.
 */
function isWeeklyHours(
    value: unknown,
): value is Record<string, { opens: string; closes: string }[]> {
    return (
        typeof value === 'object' &&
        value !== null &&
        !Array.isArray(value) &&
        Object.keys(value).every((key) => /^[1-7]$/.test(key))
    );
}

/**
 * The change a revert undid, as a revert logs it: `{ id, event }`.
 */
function isRevertedChange(
    value: unknown,
): value is { id: number; event: string } {
    return typeof value === 'object' && value !== null && 'event' in value;
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (isWeeklyHours(value)) {
        const days = Object.entries(value)
            .filter(([, periods]) => periods.length > 0)
            .map(
                ([day, periods]) =>
                    `${weekdays[Number(day) - 1]} ${periods
                        .map((period) => `${period.opens}–${period.closes}`)
                        .join(', ')}`,
            );

        return days.length > 0 ? days.join('; ') : 'Closed every day';
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
    actions,
}: {
    activities: App.Data.Admin.ActivityData[];
    /** Buttons shown under an entry, such as "Mark reviewed". */
    actions?: (activity: App.Data.Admin.ActivityData) => ReactNode;
}) {
    if (activities.length === 0) {
        return (
            <Empty className="border p-6">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <History />
                    </EmptyMedia>
                    <EmptyTitle>No changes yet</EmptyTitle>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <ol className="relative space-y-6 border-l pl-6">
            {activities.map((activity) => (
                <li key={activity.id} className="relative">
                    <span className="absolute top-1.5 -left-[29px] size-2.5 rounded-full border-2 border-background bg-muted-foreground" />

                    <div className="flex flex-wrap items-baseline gap-x-2 text-sm">
                        <span className="font-medium">
                            {eventLabel(activity.event)}
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

                    <ActivityChanges activity={activity} />

                    {actions && (
                        <div className="mt-2 flex flex-wrap gap-2">
                            {actions(activity)}
                        </div>
                    )}
                </li>
            ))}
        </ol>
    );
}

/**
 * Each field an activity changed, old → new, and any other details it recorded.
 */
export function ActivityChanges({
    activity,
}: {
    activity: App.Data.Admin.ActivityData;
}) {
    return (
        <>
            {activity.changes.length > 0 && (
                <dl className="mt-2 grid gap-1 text-sm">
                    {activity.changes.map((change) => (
                        <div
                            key={change.field}
                            className="flex flex-wrap items-center gap-x-2"
                        >
                            <dt className="text-muted-foreground">
                                {fieldLabel(change.field)}
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
                    {Object.entries(activity.properties).map(([key, value]) => (
                        <div key={key} className="flex gap-x-2">
                            <dt className="text-muted-foreground">
                                {fieldLabel(key)}
                            </dt>
                            <dd>
                                {key === 'reverts' && isRevertedChange(value)
                                    ? eventLabel(value.event)
                                    : formatValue(value)}
                            </dd>
                        </div>
                    ))}
                </dl>
            )}
        </>
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
