import { Deferred, Head, Link, setLayoutProps } from '@inertiajs/react';
import ActionButton from '@/components/action-button';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import Detail from '@/components/detail';
import Heading from '@/components/heading';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { show as showBusiness } from '@/routes/admin/businesses';
import { index, reactivate, show, suspend } from '@/routes/admin/users';

type Props = {
    user: App.Data.Admin.UserData;
    memberships: App.Data.Admin.MembershipData[];
    activities?: App.Data.Admin.ActivityData[];
};

export default function ShowUser({ user, memberships, activities }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Users', href: index() },
            { title: user.name, href: show(user.id) },
        ],
    });

    return (
        <>
            <Head title={user.name} />

            <div className="flex flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {user.name}
                            </h1>
                            {user.is_admin && (
                                <Badge variant="secondary">Admin</Badge>
                            )}
                            <StatusBadge
                                status={
                                    user.suspended_at ? 'suspended' : 'active'
                                }
                            />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {user.email}
                        </p>
                    </div>

                    {user.suspended_at ? (
                        <ActionButton
                            form={reactivate.form(user.id)}
                            variant="outline"
                        >
                            Reactivate
                        </ActionButton>
                    ) : (
                        <ReasonDialog
                            trigger={
                                <Button variant="destructive">Suspend</Button>
                            }
                            title={`Suspend ${user.name}?`}
                            description="They are logged out and cannot log in to any business until reactivated."
                            form={suspend.form(user.id)}
                            submitLabel="Suspend"
                            destructive
                        />
                    )}
                </div>

                <PageErrors />

                {user.suspended_at && (
                    <Notice title="Suspended">
                        {formatDateTime(user.suspended_at)} by{' '}
                        {user.suspended_by_name ?? 'an admin'}:{' '}
                        {user.suspension_reason}
                    </Notice>
                )}

                <section>
                    <Heading variant="small" title="Account" />
                    <dl className="mt-3 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                        <Detail label="Email verified">
                            {user.is_email_verified ? 'Yes' : 'No'}
                        </Detail>
                        <Detail label="Two-factor authentication">
                            {user.has_two_factor ? 'On' : 'Off'}
                        </Detail>
                        <Detail label="Temporary password">
                            {user.must_change_password
                                ? 'Must change it at next login'
                                : 'No'}
                        </Detail>
                        <Detail label="Last login">
                            {formatDateTime(user.last_login_at)}
                        </Detail>
                        <Detail label="Joined">
                            {formatDate(user.created_at)}
                        </Detail>
                    </dl>
                </section>

                <section className="space-y-3">
                    <Heading variant="small" title="Businesses" />
                    {memberships.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Not a member of any business.
                        </p>
                    ) : (
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Business</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Outlets</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {memberships.map((membership) => (
                                        <TableRow key={membership.id}>
                                            <TableCell className="font-medium">
                                                <Link
                                                    href={showBusiness(
                                                        membership.business_id,
                                                    )}
                                                    className="hover:underline"
                                                >
                                                    {membership.business_name}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="capitalize">
                                                {membership.role}
                                            </TableCell>
                                            <TableCell className="whitespace-normal">
                                                {membership.role === 'owner'
                                                    ? 'All outlets'
                                                    : membership.outlets
                                                          .map(
                                                              (outlet) =>
                                                                  outlet.name,
                                                          )
                                                          .join(', ')}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    status={
                                                        membership.suspended_at
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
                    )}
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title="Activity"
                        description="The latest 100 changes to this login. Passwords are never logged."
                    />
                    <Deferred data="activities" fallback={<ActivitySkeleton />}>
                        <ActivityTimeline activities={activities ?? []} />
                    </Deferred>
                </section>
            </div>
        </>
    );
}
