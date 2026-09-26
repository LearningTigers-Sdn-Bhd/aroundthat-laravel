import { Head, Link } from '@inertiajs/react';
import { Plus, Ticket } from 'lucide-react';
import type { DataTableColumn } from '@/components/data-table';
import DataTable from '@/components/data-table';
import Heading from '@/components/heading';
import ModalButtonLink from '@/components/modal-button-link';
import StatusBadge from '@/components/status-badge';
import { formatDate } from '@/lib/format';
import { discountSummary } from '@/lib/offers';
import { create, edit, index } from '@/routes/offers';

type Props = {
    offers: Illuminate.LengthAwarePaginator<number, App.Data.OfferData>;
    canCreate: boolean;
};

const columns: DataTableColumn<App.Data.OfferData>[] = [
    {
        key: 'name',
        header: 'Offer',
        sort: 'name',
        cell: (offer) => (
            <Link href={edit(offer.id)} className="font-medium hover:underline">
                {offer.name}
            </Link>
        ),
    },
    {
        key: 'discount',
        header: 'Discount',
        cell: (offer) => discountSummary(offer),
    },
    {
        key: 'starts_at',
        header: 'Runs',
        sort: 'starts_at',
        className: 'whitespace-nowrap',
        cell: (offer) =>
            `${formatDate(offer.starts_at)} – ${formatDate(offer.ends_at)}`,
    },
    {
        key: 'issued',
        header: 'Issued',
        cell: (offer) =>
            offer.voucher_limit === null
                ? offer.issued_count
                : `${offer.issued_count} / ${offer.voucher_limit}`,
    },
    {
        key: 'state',
        header: 'Status',
        cell: (offer) => <StatusBadge status={offer.state} />,
    },
];

export default function OffersIndex({ offers, canCreate }: Props) {
    return (
        <>
            <Head title="Offers" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Offers"
                        description="Deals guests can claim as vouchers and use at your outlets."
                    />
                    {canCreate && (
                        <ModalButtonLink href={create().url}>
                            <Plus />
                            Add offer
                        </ModalButtonLink>
                    )}
                </div>

                <DataTable
                    rows={offers}
                    columns={columns}
                    rowKey={(offer) => offer.id}
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
                    ]}
                    emptyTitle="No offers yet"
                    emptyIcon={Ticket}
                />
            </div>
        </>
    );
}

OffersIndex.layout = {
    breadcrumbs: [{ title: 'Offers', href: index() }],
};
