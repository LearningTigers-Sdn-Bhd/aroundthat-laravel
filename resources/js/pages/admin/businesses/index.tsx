import { Head, Link } from '@inertiajs/react';
import { Building2, Plus } from 'lucide-react';
import type { DataTableColumn } from '@/components/data-table';
import DataTable from '@/components/data-table';
import Heading from '@/components/heading';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import ButtonLink from '@/components/button-link';
import { formatDate, humanize } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { create, index, show } from '@/routes/admin/businesses';

type Props = {
    businesses: Illuminate.LengthAwarePaginator<
        number,
        App.Data.Admin.BusinessData
    >;
    onboardingStatuses: App.Enums.OnboardingStatus[];
};

const columns: DataTableColumn<App.Data.Admin.BusinessData>[] = [
    {
        key: 'name',
        header: 'Business',
        sort: 'name',
        cell: (business) => (
            <Link
                href={show(business.id)}
                className="font-medium hover:underline"
            >
                {business.name}
            </Link>
        ),
    },
    {
        key: 'contact_email',
        header: 'Contact',
        cell: (business) => business.contact_email,
    },
    {
        key: 'status',
        header: 'Status',
        cell: (business) => <StatusBadge status={recordStatus(business)} />,
    },
    {
        key: 'submitted_at',
        header: 'Submitted',
        sort: 'submitted_at',
        cell: (business) => formatDate(business.submitted_at),
    },
    {
        key: 'created_at',
        header: 'Created',
        sort: 'created_at',
        cell: (business) => formatDate(business.created_at),
    },
];

export default function BusinessesIndex({
    businesses,
    onboardingStatuses,
}: Props) {
    return (
        <>
            <Head title="Businesses" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Businesses"
                        description="Every business on the platform."
                    />
                    <ButtonLink href={create()}>
                            <Plus />
                            New business
                        </ButtonLink>
                </div>

                <DataTable
                    rows={businesses}
                    columns={columns}
                    rowKey={(business) => business.id}
                    searchPlaceholder="Search name, registration or email"
                    filters={[
                        {
                            name: 'onboarding_status',
                            label: 'Statuses',
                            options: onboardingStatuses.map((status) => ({
                                value: status,
                                label: humanize(status),
                            })),
                        },
                        {
                            name: 'suspended',
                            label: 'Suspension states',
                            options: [
                                { value: 'true', label: 'Suspended' },
                                { value: 'false', label: 'Not suspended' },
                            ],
                        },
                    ]}
                    emptyTitle="No businesses yet"
                    emptyIcon={Building2}
                />
            </div>
        </>
    );
}

BusinessesIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Businesses', href: index() },
    ],
};
