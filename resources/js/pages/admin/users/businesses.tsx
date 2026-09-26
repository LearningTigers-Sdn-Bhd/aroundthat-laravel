import { Head, Link, setLayoutProps } from '@inertiajs/react';
import UserPage from '@/components/admin/users/user-page';
import StatusBadge from '@/components/status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes/admin';
import { show as showBusiness } from '@/routes/admin/businesses';
import { index, show } from '@/routes/admin/users';
import { index as businessesIndex } from '@/routes/admin/users/businesses';

type Props = {
    user: App.Data.Admin.UserData;
    memberships: App.Data.Admin.MembershipData[];
};

export default function UserBusinesses({ user, memberships }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Users', href: index() },
            { title: user.name, href: show(user.id) },
            { title: 'Businesses', href: businessesIndex(user.id) },
        ],
    });

    return (
        <>
            <Head title={`${user.name} businesses`} />

            <UserPage user={user}>
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
            </UserPage>
        </>
    );
}
