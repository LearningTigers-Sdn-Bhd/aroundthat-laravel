import { Head, Link } from '@inertiajs/react';
import { Users } from 'lucide-react';
import type { DataTableColumn } from '@/components/data-table';
import DataTable from '@/components/data-table';
import Heading from '@/components/heading';
import StatusBadge from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/users';

const columns: DataTableColumn<App.Data.Admin.UserData>[] = [
    {
        key: 'name',
        header: 'Name',
        sort: 'name',
        cell: (user) => (
            <span className="flex items-center gap-2">
                <Link
                    href={show(user.id)}
                    className="font-medium hover:underline"
                >
                    {user.name}
                </Link>
                {user.is_admin && <Badge variant="secondary">Admin</Badge>}
            </span>
        ),
    },
    {
        key: 'email',
        header: 'Email',
        sort: 'email',
        cell: (user) => user.email,
    },
    {
        key: 'status',
        header: 'Status',
        cell: (user) => (
            <StatusBadge status={user.suspended_at ? 'suspended' : 'active'} />
        ),
    },
    {
        key: 'last_login_at',
        header: 'Last login',
        sort: 'last_login_at',
        cell: (user) => formatDateTime(user.last_login_at),
    },
    {
        key: 'created_at',
        header: 'Joined',
        sort: 'created_at',
        cell: (user) => formatDate(user.created_at),
    },
];

export default function UsersIndex({
    users,
}: {
    users: Illuminate.LengthAwarePaginator<number, App.Data.Admin.UserData>;
}) {
    return (
        <>
            <Head title="Users" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Users"
                    description="Every login on the platform."
                />

                <DataTable
                    rows={users}
                    columns={columns}
                    rowKey={(user) => user.id}
                    searchPlaceholder="Search name or email"
                    filters={[
                        {
                            name: 'suspended',
                            label: 'Suspension states',
                            options: [
                                { value: 'true', label: 'Suspended' },
                                { value: 'false', label: 'Not suspended' },
                            ],
                        },
                        {
                            name: 'is_admin',
                            label: 'Roles',
                            options: [
                                { value: 'true', label: 'Admins' },
                                { value: 'false', label: 'Not admins' },
                            ],
                        },
                    ]}
                    emptyTitle="No users yet"
                    emptyIcon={Users}
                />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Users', href: index() },
    ],
};
