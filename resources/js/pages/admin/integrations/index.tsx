import { Head, Link } from '@inertiajs/react';
import { Plug, Plus } from 'lucide-react';
import IntegrationFields, {
    integrationTypes,
} from '@/components/admin/integrations/integration-fields';
import type { DataTableColumn } from '@/components/data-table';
import DataTable from '@/components/data-table';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index, show, store } from '@/routes/admin/integrations';

const columns: DataTableColumn<App.Data.Admin.IntegrationData>[] = [
    {
        key: 'name',
        header: 'Name',
        sort: 'name',
        cell: (integration) => (
            <Link
                href={show(integration.id)}
                className="font-medium hover:underline"
            >
                {integration.name}
            </Link>
        ),
    },
    {
        key: 'type',
        header: 'Type',
        cell: (integration) => integrationTypes[integration.type],
    },
    {
        key: 'status',
        header: 'Status',
        cell: (integration) => (
            <StatusBadge
                status={
                    integration.suspended_at
                        ? 'suspended'
                        : integration.is_usable
                          ? 'active'
                          : 'expired'
                }
            />
        ),
    },
    {
        key: 'keys_count',
        header: 'API keys',
        cell: (integration) => integration.keys_count,
    },
    {
        key: 'created_at',
        header: 'Added',
        sort: 'created_at',
        cell: (integration) => formatDate(integration.created_at),
    },
];

export default function IntegrationsIndex({
    integrations,
}: {
    integrations: Illuminate.LengthAwarePaginator<
        number,
        App.Data.Admin.IntegrationData
    >;
}) {
    return (
        <>
            <Head title="Integrations" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Integrations"
                        description="Partners that read public places and send engagement events through the partner API."
                    />
                    <FormDialog
                        trigger={
                            <Button>
                                <Plus />
                                New integration
                            </Button>
                        }
                        title="New integration"
                        form={store.form()}
                        submitLabel="Add"
                    >
                        {(errors) => <IntegrationFields errors={errors} />}
                    </FormDialog>
                </div>

                <DataTable
                    rows={integrations}
                    columns={columns}
                    rowKey={(integration) => integration.id}
                    searchPlaceholder="Search name"
                    filters={[
                        {
                            name: 'type',
                            label: 'Types',
                            options: Object.entries(integrationTypes).map(
                                ([value, label]) => ({ value, label }),
                            ),
                        },
                    ]}
                    emptyTitle="No integrations yet"
                    emptyIcon={Plug}
                />
            </div>
        </>
    );
}

IntegrationsIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Integrations', href: index() },
    ],
};
