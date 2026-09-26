import { Form, Head, setLayoutProps } from '@inertiajs/react';
import OfferOutletsField from '@/components/app/offers/offer-outlets-field';
import OfferPage from '@/components/app/offers/offer-page';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { edit, index } from '@/routes/offers';
import { index as outletsIndex, update } from '@/routes/offers/outlets';

type Props = {
    offer: App.Data.OfferData;
    outletOptions: App.Data.OutletOptionData[];
    can: { update: boolean };
};

export default function OfferOutlets({ offer, outletOptions, can }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Offers', href: index() },
            { title: offer.name, href: edit(offer.id) },
            { title: 'Outlets', href: outletsIndex(offer.id) },
        ],
    });

    const ownOutletIds = offer.outlets
        .filter((outlet) => !outlet.is_sponsored)
        .map((outlet) => outlet.id);
    const sponsoredOutlets = offer.outlets.filter(
        (outlet) => outlet.is_sponsored,
    );

    return (
        <>
            <Head title={`${offer.name} outlets`} />

            <OfferPage
                offer={offer}
                canUpdate={can.update}
                errorsShownInForms={['outlet_ids']}
            >
                <Form
                    {...update.form(offer.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <fieldset
                            disabled={!can.update}
                            className="space-y-4 disabled:opacity-60"
                        >
                            <Heading
                                variant="small"
                                title="Where vouchers can be used"
                                description="Guests can use this offer's vouchers at the outlets you tick."
                            />

                            <OfferOutletsField
                                outletOptions={outletOptions}
                                defaultOutletIds={ownOutletIds}
                                error={errors.outlet_ids}
                                hideLegend
                            />

                            {sponsoredOutlets.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-sm font-medium">
                                        Sponsored outlets
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Outlets of other businesses that an
                                        admin added. Only an admin can change
                                        them.
                                    </p>
                                    <ul className="space-y-1 text-sm">
                                        {sponsoredOutlets.map((outlet) => (
                                            <li
                                                key={outlet.id}
                                                className="flex items-center gap-2"
                                            >
                                                {outlet.name}
                                                <Badge variant="outline">
                                                    {outlet.business_name}
                                                </Badge>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {can.update && (
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Save outlets
                                    </Button>
                                </div>
                            )}
                        </fieldset>
                    )}
                </Form>
            </OfferPage>
        </>
    );
}
