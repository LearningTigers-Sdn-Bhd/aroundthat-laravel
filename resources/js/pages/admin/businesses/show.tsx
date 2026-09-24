import { Deferred, Head, setLayoutProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ChangeActions from '@/components/admin/change-actions';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import BusinessActions from '@/components/admin/businesses/business-actions';
import OutletsTable from '@/components/admin/businesses/outlets-table';
import Detail from '@/components/detail';
import Heading from '@/components/heading';
import InvitationsTable from '@/components/invitations-table';
import MembersTable from '@/components/members-table';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import ModalButtonLink from '@/components/modal-button-link';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/businesses';
import { create as createOutlet } from '@/routes/admin/businesses/outlets';
import { destroy, resend } from '@/routes/admin/invitations';

type Props = {
    business: App.Data.Admin.BusinessData;
    outlets: App.Data.Admin.OutletData[];
    members: App.Data.MemberData[];
    invitations: App.Data.InvitationData[];
    activities?: App.Data.Admin.ActivityData[];
};

export default function ShowBusiness({
    business,
    outlets,
    members,
    invitations,
    activities,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Businesses', href: index() },
            { title: business.name, href: show(business.id) },
        ],
    });

    return (
        <>
            <Head title={business.name} />

            <div className="flex flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {business.name}
                            </h1>
                            <StatusBadge status={recordStatus(business)} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Created {formatDate(business.created_at)}
                        </p>
                    </div>

                    <BusinessActions business={business} />
                </div>

                <PageErrors />

                {business.suspended_at && (
                    <Notice title="Suspended">
                        {formatDateTime(business.suspended_at)} by{' '}
                        {business.suspended_by_name ?? 'an admin'}:{' '}
                        {business.suspension_reason}
                    </Notice>
                )}
                {business.onboarding_status === 'rejected' && (
                    <Notice title="Rejected">
                        {business.rejection_reason}
                    </Notice>
                )}

                <section>
                    <Heading variant="small" title="Details" />
                    <dl className="mt-3 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                        <Detail label="Registered name">
                            {business.registered_name}
                        </Detail>
                        <Detail label="Registration number">
                            {business.registration_number}
                        </Detail>
                        <Detail label="Contact email">
                            {business.contact_email}
                        </Detail>
                        <Detail label="Contact phone">
                            {business.contact_phone}
                        </Detail>
                        <Detail label="Address">{business.address}</Detail>
                        <Detail label="Timezone">{business.timezone}</Detail>
                        <Detail label="Submitted">
                            {formatDateTime(business.submitted_at)}
                        </Detail>
                        <Detail label="Approved">
                            {business.approved_at
                                ? `${formatDateTime(business.approved_at)} by ${business.approved_by_name ?? 'an admin'}`
                                : null}
                        </Detail>
                    </dl>
                </section>

                <section className="space-y-3">
                    <div className="flex items-center justify-between gap-4">
                        <Heading variant="small" title="Outlets" />
                        {!business.suspended_at && (
                            <ModalButtonLink
                                variant="outline"
                                size="sm"
                                href={createOutlet(business.id).url}
                            >
                                <Plus />
                                Add outlet
                            </ModalButtonLink>
                        )}
                    </div>
                    <OutletsTable outlets={outlets} />
                </section>

                <section className="space-y-3">
                    <Heading variant="small" title="Members" />
                    <MembersTable members={members} />
                </section>

                {invitations.length > 0 && (
                    <section className="space-y-3">
                        <Heading variant="small" title="Open invitations" />
                        <InvitationsTable
                            invitations={invitations}
                            resend={resend}
                            cancel={destroy}
                        />
                    </section>
                )}

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title="Activity"
                        description="The latest 100 changes to the business, its outlets, members and invitations."
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
