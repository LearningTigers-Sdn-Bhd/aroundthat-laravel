import { Deferred, Head, Link, setLayoutProps } from '@inertiajs/react';
import ChangeActions from '@/components/admin/change-actions';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import HostOutlet from '@/components/admin/outlets/host-outlet';
import OutletActions from '@/components/admin/outlets/outlet-actions';
import Detail from '@/components/detail';
import Heading from '@/components/heading';
import PlacePreview from '@/components/place-preview';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index, show as showBusiness } from '@/routes/admin/businesses';
import { show } from '@/routes/admin/outlets';

type Props = {
    outlet: App.Data.Admin.OutletData;
    hostCandidates: App.Data.Admin.HostOutletOptionData[];
    hostedOutlets: App.Data.Admin.OutletData[];
    preview: App.Data.PlacePreviewData;
    activities?: App.Data.Admin.ActivityData[];
};

export default function ShowOutlet({
    outlet,
    hostCandidates,
    hostedOutlets,
    preview,
    activities,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Businesses', href: index() },
            {
                title: outlet.business_name,
                href: showBusiness(outlet.business_id),
            },
            { title: outlet.name, href: show(outlet.id) },
        ],
    });

    return (
        <>
            <Head title={outlet.name} />

            <div className="flex flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {outlet.name}
                            </h1>
                            <StatusBadge status={recordStatus(outlet)} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Outlet of{' '}
                            <Link
                                href={showBusiness(outlet.business_id)}
                                className="hover:underline"
                            >
                                {outlet.business_name}
                            </Link>
                            {outlet.is_operational
                                ? ' · Trading'
                                : ' · Not trading'}
                        </p>
                    </div>

                    <OutletActions outlet={outlet} />
                </div>

                <PageErrors except={['host_outlet_id']} />

                {outlet.suspended_at && (
                    <Notice title="Suspended">
                        {formatDateTime(outlet.suspended_at)} by{' '}
                        {outlet.suspended_by_name ?? 'an admin'}:{' '}
                        {outlet.suspension_reason}
                    </Notice>
                )}
                {outlet.onboarding_status === 'rejected' && (
                    <Notice title="Rejected">{outlet.rejection_reason}</Notice>
                )}
                {outlet.hidden_at && (
                    <Notice title="Hidden from visitors">
                        {formatDateTime(outlet.hidden_at)}:{' '}
                        {outlet.hidden_reason}
                    </Notice>
                )}

                <section>
                    <Heading variant="small" title="Details" />
                    <dl className="mt-3 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                        <Detail label="Address">
                            {[
                                outlet.address_line_1,
                                outlet.address_line_2,
                                `${outlet.postcode} ${outlet.city}`,
                                `${outlet.state}, ${outlet.country_code}`,
                            ]
                                .filter(Boolean)
                                .join('\n')}
                        </Detail>
                        <Detail label="Timezone">{outlet.timezone}</Detail>
                        <Detail label="Contact email">
                            {outlet.contact_email}
                        </Detail>
                        <Detail label="Contact phone">
                            {outlet.contact_phone}
                        </Detail>
                        <Detail label="Submitted">
                            {formatDateTime(outlet.submitted_at)}
                        </Detail>
                        <Detail label="Approved">
                            {outlet.approved_at
                                ? `${formatDateTime(outlet.approved_at)} by ${outlet.approved_by_name ?? 'an admin'}`
                                : null}
                        </Detail>
                        <Detail label="Archived">
                            {outlet.archived_at
                                ? formatDateTime(outlet.archived_at)
                                : null}
                        </Detail>
                    </dl>
                </section>

                <HostOutlet outlet={outlet} candidates={hostCandidates} />

                <section className="max-w-3xl space-y-3">
                    <Heading
                        variant="small"
                        title="Public page"
                        description={
                            preview.place.is_public
                                ? 'Visitors can see this now.'
                                : outlet.hidden_at
                                  ? 'Hidden by an admin, so visitors cannot see it.'
                                  : preview.place.is_listed
                                  ? 'Listed by the owner, but not visible yet.'
                                  : 'Not listed by the owner.'
                        }
                    />
                    <PlacePreview preview={preview} />
                </section>

                {hostedOutlets.length > 0 && (
                    <section className="space-y-3">
                        <Heading
                            variant="small"
                            title="Outlets inside this one"
                        />
                        <ul className="divide-y rounded-md border text-sm">
                            {hostedOutlets.map((hosted) => (
                                <li
                                    key={hosted.id}
                                    className="flex items-center justify-between gap-4 px-3 py-2"
                                >
                                    <div className="flex flex-col">
                                        <Link
                                            href={show(hosted.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {hosted.name}
                                        </Link>
                                        <span className="text-muted-foreground">
                                            {hosted.business_name}
                                        </span>
                                    </div>
                                    <StatusBadge
                                        status={recordStatus(hosted)}
                                    />
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title="Activity"
                        description="The latest 100 changes to this outlet."
                    />
                    <Deferred data="activities" fallback={<ActivitySkeleton />}>
                        <ActivityTimeline
                            activities={activities ?? []}
                            actions={(activity) => (
                                <ChangeActions activity={activity} />
                            )}
                        />
                    </Deferred>
                </section>
            </div>
        </>
    );
}
