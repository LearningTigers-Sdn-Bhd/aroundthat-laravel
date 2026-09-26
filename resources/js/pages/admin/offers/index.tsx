import { Head, Link } from '@inertiajs/react';
import { Ticket } from 'lucide-react';
import type { DataTableColumn } from '@/components/data-table';
import DataTable from '@/components/data-table';
import Heading from '@/components/heading';
import StatusBadge from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/format';
import { discountSummary } from '@/lib/offers';
import { dashboard } from '@/routes/admin';
import { show as showBusiness } from '@/routes/admin/businesses';
import { index, show } from '@/routes/admin/offers';

const columns: DataTableColumn<App.Data.Admin.OfferData>[] = [
    {
        key: 'name',
        header: 'Offer',
        sort: 'name',
        cell: (row) => (
            <div className="flex flex-col">
                <Link
                    href={show(row.offer.id)}
                    className="font-medium hover:underline"
                >
                    {row.offer.name}
                </Link>
                <Link
                    href={showBusiness(row.business_id)}
                    className="text-muted-foreground hover:underline"
                >
                    {row.business_name}
                </Link>
            </div>
        ),
    },
    {
        key: 'discount',
        header: 'Discount',
        cell: (row) => discountSummary(row.offer),
    },
    {
        key: 'ends_at',
        header: 'Ends',
        sort: 'ends_at',
        cell: (row) => formatDate(row.offer.ends_at),
    },
    {
        key: 'issued',
        header: 'Issued',
        cell: (row) =>
            row.offer.voucher_limit === null
                ? row.offer.issued_count
                : `${row.offer.issued_count} / ${row.offer.voucher_limit}`,
    },
    {
        key: 'state',
        header: 'Status',
        cell: (row) => (
            <div className="flex items-center gap-2">
                <StatusBadge status={row.offer.state} />
                {row.is_sponsored && <Badge variant="outline">Sponsored</Badge>}
            </div>
        ),
    },
];

export default function OffersIndex({
    offers,
}: {
    offers: Illuminate.LengthAwarePaginator<number, App.Data.Admin.OfferData>;
}) {
    return (
        <>
            <Head title="Offers" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Offers"
                    description="Every business's voucher offers. Hide an offer to stop claims and redemptions, or add outlets of other businesses to sponsor it."
                />

                <DataTable
                    rows={offers}
                    columns={columns}
                    rowKey={(row) => row.offer.id}
                    searchPlaceholder="Search name"
                    filters={[
                        {
                            name: 'status',
                            label: 'Statuses',
                            options: [
                                { value: 'draft', label: 'Draft' },
                                { value: 'active', label: 'Active' },
                                { value: 'paused', label: 'Paused' },
                            ],
                        },
                        {
                            name: 'visibility',
                            label: 'Visibility',
                            defaultLabel: 'Hidden or not',
                            options: [
                                { value: 'hidden', label: 'Hidden' },
                                { value: 'visible', label: 'Not hidden' },
                            ],
                        },
                        {
                            name: 'sponsored',
                            label: 'Sponsorship',
                            defaultLabel: 'Sponsored or not',
                            options: [{ value: '1', label: 'Sponsored' }],
                        },
                    ]}
                    emptyTitle="No offers yet"
                    emptyIcon={Ticket}
                />
            </div>
        </>
    );
}

OffersIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Offers', href: index() },
    ],
};
