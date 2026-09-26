import { Form, Head, setLayoutProps } from '@inertiajs/react';
import OfferFields, {
    DescriptionField,
    EndsAtField,
    offerFieldNames,
    VoucherLimitField,
} from '@/components/app/offers/offer-fields';
import OfferPage from '@/components/app/offers/offer-page';
import Heading from '@/components/heading';
import Notice from '@/components/notice';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { edit, index, update } from '@/routes/offers';

type Props = {
    offer: App.Data.OfferData;
    timezone: string;
    can: { update: boolean };
};

export default function EditOffer({ offer, timezone, can }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Offers', href: index() },
            { title: offer.name, href: edit(offer.id) },
        ],
    });

    return (
        <>
            <Head title={offer.name} />

            <OfferPage
                offer={offer}
                canUpdate={can.update}
                errorsShownInForms={offerFieldNames}
            >
                <Form
                    {...update.form(offer.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <fieldset
                            disabled={!can.update}
                            className="space-y-6 disabled:opacity-60"
                        >
                            <Heading
                                variant="small"
                                title="Details"
                                description="The name, discount and dates guests see when they claim a voucher."
                            />

                            {offer.is_locked ? (
                                <>
                                    <Notice title="Terms are fixed" tone="info">
                                        Vouchers are already issued, so guests
                                        hold these terms. You can still change
                                        the description, the end date and the
                                        limit.
                                    </Notice>
                                    <DescriptionField
                                        defaultValue={offer.description}
                                        error={errors.description}
                                    />
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <EndsAtField
                                            defaultValue={offer.ends_at_local}
                                            error={errors.ends_at}
                                        />
                                        <VoucherLimitField
                                            defaultValue={offer.voucher_limit}
                                            error={errors.voucher_limit}
                                        />
                                    </div>
                                    <p className="-mt-4 text-sm text-muted-foreground">
                                        Times are in {timezone}.
                                    </p>
                                </>
                            ) : (
                                <OfferFields
                                    errors={errors}
                                    offer={offer}
                                    currency={offer.currency}
                                    timezone={timezone}
                                />
                            )}

                            {can.update && (
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Save
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
