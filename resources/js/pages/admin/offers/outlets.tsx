import { Head, setLayoutProps } from '@inertiajs/react';
import OfferOutlets from '@/components/admin/offers/offer-outlets';
import OfferPage from '@/components/admin/offers/offer-page';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/offers';
import { index as outletsIndex } from '@/routes/admin/offers/outlets';

type Props = {
    offer: App.Data.Admin.OfferData;
    sponsorCandidates: App.Data.Admin.HostOutletOptionData[];
};

export default function OfferOutletsTab({
    offer: row,
    sponsorCandidates,
}: Props) {
    const { offer } = row;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Offers', href: index() },
            { title: offer.name, href: show(offer.id) },
            { title: 'Outlets', href: outletsIndex(offer.id) },
        ],
    });

    return (
        <>
            <Head title={`${offer.name} outlets`} />

            <OfferPage offer={row} errorsShownInForms={['outlet_id']}>
                <OfferOutlets
                    offer={offer}
                    businessName={row.business_name}
                    candidates={sponsorCandidates}
                />
            </OfferPage>
        </>
    );
}
