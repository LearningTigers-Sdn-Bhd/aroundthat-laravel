import { Head, Link } from '@inertiajs/react';
import { Plus, Ticket } from 'lucide-react';
import Heading from '@/components/heading';
import ModalButtonLink from '@/components/modal-button-link';
import StatusBadge from '@/components/status-badge';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import { discountSummary } from '@/lib/offers';
import { create, edit, index } from '@/routes/offers';

type Props = {
    offers: App.Data.OfferData[];
    canCreate: boolean;
};

export default function OffersIndex({ offers, canCreate }: Props) {
    return (
        <>
            <Head title="Offers" />

            <div className="flex flex-1 flex-col gap-6 p-4">
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

                {offers.length === 0 ? (
                    <Empty className="border">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Ticket />
                            </EmptyMedia>
                            <EmptyTitle>No offers yet</EmptyTitle>
                            <EmptyDescription>
                                Create an offer, choose where it can be used,
                                then activate it.
                            </EmptyDescription>
                        </EmptyHeader>
                        {canCreate && (
                            <EmptyContent>
                                <ModalButtonLink href={create().url}>
                                    <Plus />
                                    Add offer
                                </ModalButtonLink>
                            </EmptyContent>
                        )}
                    </Empty>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Offer</TableHead>
                                    <TableHead>Discount</TableHead>
                                    <TableHead>Runs</TableHead>
                                    <TableHead>Issued</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {offers.map((offer) => (
                                    <TableRow key={offer.id}>
                                        <TableCell className="font-medium">
                                            <Link
                                                href={edit(offer.id)}
                                                className="hover:underline"
                                            >
                                                {offer.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            {discountSummary(offer)}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            {formatDate(offer.starts_at)} –{' '}
                                            {formatDate(offer.ends_at)}
                                        </TableCell>
                                        <TableCell>
                                            {offer.issued_count}
                                            {offer.voucher_limit !== null &&
                                                ` / ${offer.voucher_limit}`}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge status={offer.state} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </>
    );
}

OffersIndex.layout = {
    breadcrumbs: [{ title: 'Offers', href: index() }],
};
