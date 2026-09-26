import { Head, setLayoutProps } from '@inertiajs/react';
import OfferPage from '@/components/admin/offers/offer-page';
import Detail from '@/components/detail';
import { formatDateTime } from '@/lib/format';
import { discountSummary } from '@/lib/offers';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/offers';

type Props = {
    offer: App.Data.Admin.OfferData;
};

export default function ShowOffer({ offer: row }: Props) {
    const { offer } = row;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Offers', href: index() },
            { title: offer.name, href: show(offer.id) },
        ],
    });

    return (
        <>
            <Head title={offer.name} />

            <OfferPage offer={row}>
                <dl className="grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                    <Detail label="Description">{offer.description}</Detail>
                    <Detail label="Discount">{discountSummary(offer)}</Detail>
                    <Detail label="Starts">
                        {formatDateTime(offer.starts_at)}
                    </Detail>
                    <Detail label="Ends">
                        {formatDateTime(offer.ends_at)}
                    </Detail>
                    <Detail label="Uses per voucher">
                        {String(offer.uses_per_voucher)}
                    </Detail>
                    <Detail label="Voucher lasts">
                        {offer.voucher_valid_days
                            ? `${offer.voucher_valid_days} days`
                            : 'Until the offer ends'}
                    </Detail>
                    <Detail label="Issued">
                        {offer.voucher_limit === null
                            ? `${offer.issued_count}, no limit`
                            : `${offer.issued_count} of ${offer.voucher_limit}`}
                    </Detail>
                </dl>
            </OfferPage>
        </>
    );
}
