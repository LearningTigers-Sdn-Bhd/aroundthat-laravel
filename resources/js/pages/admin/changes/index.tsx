import { Form, Head, Link } from '@inertiajs/react';
import { CheckCheck, History } from 'lucide-react';
import { ActivityChanges, eventLabel } from '@/components/activity-timeline';
import ChangeActions from '@/components/admin/change-actions';
import type { DataTableColumn } from '@/components/data-table';
import DataTable from '@/components/data-table';
import Heading from '@/components/heading';
import PageErrors from '@/components/page-errors';
import StatusBadge from '@/components/status-badge';
import type { Status } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { formatDateTime } from '@/lib/format';
import { show as showBusiness } from '@/routes/admin/businesses';
import { reviewMany } from '@/routes/admin/changes';
import { show as showOutlet } from '@/routes/admin/outlets';

type Props = {
    changes: Illuminate.LengthAwarePaginator<number, App.Data.Admin.ChangeData>;
};

function changeStatus(change: App.Data.Admin.ChangeData): Status {
    if (change.reverted_at) {
        return 'reverted';
    }

    return change.activity.reviewed_at ? 'reviewed' : 'unreviewed';
}

export default function ChangesIndex({ changes }: Props) {
    const unreviewedIds = changes.data
        .filter((change) => !change.activity.reviewed_at)
        .map((change) => change.activity.id);

    const columns: DataTableColumn<App.Data.Admin.ChangeData>[] = [
        {
            key: 'created_at',
            header: 'When',
            sort: 'created_at',
            className: 'w-0 whitespace-nowrap align-top',
            cell: (change) =>
                change.activity.created_at &&
                formatDateTime(change.activity.created_at),
        },
        {
            key: 'subject',
            header: 'Place',
            className: 'align-top',
            cell: (change) => <ChangeSubject subject={change.subject} />,
        },
        {
            key: 'change',
            header: 'Change',
            className: 'align-top whitespace-normal',
            cell: (change) => (
                <div className="min-w-64">
                    <div className="font-medium">
                        {eventLabel(change.activity.event)}
                    </div>
                    <ActivityChanges activity={change.activity} />
                </div>
            ),
        },
        {
            key: 'causer',
            header: 'By',
            className: 'align-top',
            cell: (change) => change.activity.causer_name ?? 'System',
        },
        {
            key: 'status',
            header: 'Status',
            className: 'align-top',
            cell: (change) => (
                <div className="space-y-1">
                    <StatusBadge status={changeStatus(change)} />
                    {change.reviewed_by_name && !change.reverted_at && (
                        <div className="text-xs text-muted-foreground">
                            by {change.reviewed_by_name}
                        </div>
                    )}
                </div>
            ),
        },
        {
            key: 'actions',
            header: '',
            className: 'w-0 align-top',
            cell: (change) => (
                <div className="flex items-center justify-end gap-1">
                    <ChangeActions activity={change.activity} />
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Changes" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Changes"
                        description="What owners changed on their public pages. Their edits are already live; review them here."
                    />
                    {unreviewedIds.length > 0 && (
                        <Form
                            {...reviewMany.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <>
                                    {unreviewedIds.map((id) => (
                                        <input
                                            key={id}
                                            type="hidden"
                                            name="ids[]"
                                            value={id}
                                        />
                                    ))}
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <Spinner />
                                        ) : (
                                            <CheckCheck />
                                        )}
                                        Mark shown as reviewed
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </div>

                <PageErrors />

                <DataTable
                    rows={changes}
                    columns={columns}
                    rowKey={(change) => String(change.activity.id)}
                    searchPlaceholder="Search outlets and businesses"
                    filters={[
                        {
                            name: 'status',
                            label: 'Statuses',
                            defaultLabel: 'To review',
                            options: [
                                { value: 'reviewed', label: 'Reviewed' },
                                { value: 'reverted', label: 'Reverted' },
                                { value: 'all', label: 'All changes' },
                            ],
                        },
                        {
                            name: 'subject_type',
                            label: 'Places',
                            options: [
                                { value: 'outlet', label: 'Outlets' },
                                { value: 'business', label: 'Businesses' },
                            ],
                        },
                        {
                            name: 'event',
                            label: 'Changes',
                            options: [
                                'details_changed',
                                'public_profile_changed',
                                'category_changed',
                                'tags_changed',
                                'hours_changed',
                                'date_exceptions_changed',
                                'image_added',
                                'image_changed',
                                'image_removed',
                                'images_reordered',
                            ].map((event) => ({
                                value: event,
                                label: eventLabel(event),
                            })),
                        },
                    ]}
                    emptyTitle="Nothing to review"
                    emptyIcon={History}
                />
            </div>
        </>
    );
}

function ChangeSubject({
    subject,
}: {
    subject: App.Data.Admin.ChangeSubjectData | null;
}) {
    if (!subject) {
        return <span className="text-muted-foreground">Deleted</span>;
    }

    return (
        <div className="space-y-0.5">
            <Link
                href={
                    subject.type === 'outlet'
                        ? showOutlet(subject.id)
                        : showBusiness(subject.id)
                }
                className="font-medium hover:underline"
            >
                {subject.name}
            </Link>
            <div className="text-xs text-muted-foreground">
                {subject.business_name ?? 'Business'}
            </div>
        </div>
    );
}
