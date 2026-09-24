import { Deferred, Head, setLayoutProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import ActionButton from '@/components/action-button';
import ActivityTimeline from '@/components/activity-timeline';
import Heading from '@/components/heading';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import {
    approve,
    index,
    reactivate,
    reject,
    show,
    suspend,
} from '@/routes/admin/businesses';
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
                    <Heading variant="small" title="Outlets" />
                    <OutletsTable outlets={outlets} />
                </section>

                <section className="space-y-3">
                    <Heading variant="small" title="Members" />
                    <MembersTable members={members} />
                </section>

                {invitations.length > 0 && (
                    <section className="space-y-3">
                        <Heading variant="small" title="Open invitations" />
                        <InvitationsTable invitations={invitations} />
                    </section>
                )}

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title="Activity"
                        description="The latest 100 changes to the business, its outlets, members and invitations."
                    />
                    <Deferred data="activities" fallback={<ActivitySkeleton />}>
                        <ActivityTimeline activities={activities ?? []} />
                    </Deferred>
                </section>
            </div>
        </>
    );
}

function BusinessActions({
    business,
}: {
    business: App.Data.Admin.BusinessData;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {business.onboarding_status === 'pending' && (
                <>
                    <ActionButton form={approve.form(business.id)}>
                        Approve
                    </ActionButton>
                    <ReasonDialog
                        trigger={<Button variant="outline">Reject</Button>}
                        title={`Reject ${business.name}?`}
                        description="The owner sees this reason, fixes the details and submits again."
                        form={reject.form(business.id)}
                        submitLabel="Reject"
                        destructive
                    />
                </>
            )}

            {business.suspended_at ? (
                <ActionButton
                    form={reactivate.form(business.id)}
                    variant="outline"
                >
                    Reactivate
                </ActionButton>
            ) : (
                <ReasonDialog
                    trigger={<Button variant="destructive">Suspend</Button>}
                    title={`Suspend ${business.name}?`}
                    description="Members keep their logins but cannot change the business or its outlets while it is suspended."
                    form={suspend.form(business.id)}
                    submitLabel="Suspend"
                    destructive
                />
            )}
        </div>
    );
}

function OutletsTable({ outlets }: { outlets: App.Data.Admin.OutletData[] }) {
    if (outlets.length === 0) {
        return <Empty>No outlets yet.</Empty>;
    }

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Outlet</TableHead>
                        <TableHead>City</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Inside</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {outlets.map((outlet) => (
                        <TableRow key={outlet.id}>
                            <TableCell className="font-medium">
                                {outlet.name}
                            </TableCell>
                            <TableCell>{outlet.city}</TableCell>
                            <TableCell>
                                <StatusBadge status={recordStatus(outlet)} />
                            </TableCell>
                            <TableCell>
                                {outlet.host_outlet?.name ?? '—'}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function MembersTable({ members }: { members: App.Data.MemberData[] }) {
    if (members.length === 0) {
        return <Empty>No members yet.</Empty>;
    }

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Member</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead>Outlets</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {members.map((member) => (
                        <TableRow key={member.id}>
                            <TableCell>
                                <p className="font-medium">{member.name}</p>
                                <p className="text-muted-foreground">
                                    {member.email}
                                </p>
                            </TableCell>
                            <TableCell className="capitalize">
                                {member.role}
                            </TableCell>
                            <TableCell className="whitespace-normal">
                                {member.role === 'owner'
                                    ? 'All outlets'
                                    : member.outlets
                                          .map((outlet) => outlet.name)
                                          .join(', ')}
                            </TableCell>
                            <TableCell>
                                <StatusBadge
                                    status={
                                        member.suspended_at
                                            ? 'suspended'
                                            : 'active'
                                    }
                                />
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function InvitationsTable({
    invitations,
}: {
    invitations: App.Data.InvitationData[];
}) {
    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Email</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Sent</TableHead>
                        <TableHead>Expires</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {invitations.map((invitation) => (
                        <TableRow key={invitation.id}>
                            <TableCell className="font-medium">
                                {invitation.email}
                            </TableCell>
                            <TableCell className="capitalize">
                                {invitation.role}
                            </TableCell>
                            <TableCell>
                                <StatusBadge status={invitation.status} />
                            </TableCell>
                            <TableCell>
                                {invitation.sent_at
                                    ? formatDateTime(invitation.sent_at)
                                    : 'Sending…'}
                            </TableCell>
                            <TableCell>
                                {formatDate(invitation.expires_at)}
                            </TableCell>
                            <TableCell>
                                <div className="flex justify-end gap-2">
                                    <ActionButton
                                        form={resend.form(invitation.id)}
                                        variant="outline"
                                        size="sm"
                                    >
                                        Resend
                                    </ActionButton>
                                    <ActionButton
                                        form={destroy.form(invitation.id)}
                                        variant="ghost"
                                        size="sm"
                                    >
                                        Cancel
                                    </ActionButton>
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="mt-0.5 whitespace-pre-line">{children || '—'}</dd>
        </div>
    );
}

function Notice({ title, children }: { title: string; children: ReactNode }) {
    return (
        <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
            <p className="font-medium">{title}</p>
            <p className="mt-1">{children}</p>
        </div>
    );
}

function Empty({ children }: { children: ReactNode }) {
    return <p className="text-sm text-muted-foreground">{children}</p>;
}

function ActivitySkeleton() {
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
