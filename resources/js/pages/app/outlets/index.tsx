import { Head, Link } from '@inertiajs/react';
import { Plus, Store } from 'lucide-react';
import type { DataTableColumn } from '@/components/data-table';
import DataTable from '@/components/data-table';
import Heading from '@/components/heading';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import ModalButtonLink from '@/components/modal-button-link';
import { humanize } from '@/lib/format';
import { create, edit, index } from '@/routes/outlets';

type Props = {
    outlets: Illuminate.LengthAwarePaginator<number, App.Data.OutletData>;
    onboardingStatuses: App.Enums.OnboardingStatus[];
    canCreate: boolean;
};

const columns: DataTableColumn<App.Data.OutletData>[] = [
    {
        key: 'name',
        header: 'Outlet',
        sort: 'name',
        cell: (outlet) => (
            <Link
                href={edit(outlet.id)}
                className="font-medium hover:underline"
            >
                {outlet.name}
            </Link>
        ),
    },
    {
        key: 'city',
        header: 'City',
        sort: 'city',
        cell: (outlet) => outlet.city,
    },
    {
        key: 'status',
        header: 'Status',
        cell: (outlet) => <StatusBadge status={recordStatus(outlet)} />,
    },
    {
        key: 'host_outlet',
        header: 'Inside',
        cell: (outlet) => outlet.host_outlet?.name ?? '—',
    },
];

export default function OutletsIndex({
    outlets,
    onboardingStatuses,
    canCreate,
}: Props) {
    return (
        <>
            <Head title="Outlets" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Outlets"
                        description="The places where your business trades. An admin reviews each new outlet before it goes live."
                    />
                    {canCreate && (
                        <ModalButtonLink href={create().url}>
                            <Plus />
                            Add outlet
                        </ModalButtonLink>
                    )}
                </div>

                <DataTable
                    rows={outlets}
                    columns={columns}
                    rowKey={(outlet) => outlet.id}
                    searchPlaceholder="Search name or city"
                    filters={[
                        {
                            name: 'onboarding_status',
                            label: 'Statuses',
                            options: onboardingStatuses.map((status) => ({
                                value: status,
                                label: humanize(status),
                            })),
                        },
                    ]}
                    emptyTitle="No outlets yet"
                    emptyIcon={Store}
                />
            </div>
        </>
    );
}

OutletsIndex.layout = {
    breadcrumbs: [{ title: 'Outlets', href: index() }],
};
