import { Form, Head, setLayoutProps } from '@inertiajs/react';
import OfferFields, {
    DescriptionField,
    EndsAtField,
    offerFieldNames,
    VoucherLimitField,
} from '@/components/app/offers/offer-fields';
import OfferOutletsField from '@/components/app/offers/offer-outlets-field';
import ActionButton from '@/components/action-button';
import Heading from '@/components/heading';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import StatusBadge from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { discountSummary } from '@/lib/offers';
import { activate, edit, index, pause, update } from '@/routes/offers';
import { update as updateOutlets } from '@/routes/offers/outlets';

type Props = {
    offer: App.Data.OfferData;
    timezone: string;
    outletOptions: App.Data.OutletOptionData[];
    can: { update: boolean };
};

export default function EditOffer({
    offer,
    timezone,
    outletOptions,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Offers', href: index() },
            { title: offer.name, href: edit(offer.id) },
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
            <Head title={offer.name} />

            <div className="flex max-w-3xl flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Heading
                            title={offer.name}
                            description={discountSummary(offer)}
                        />
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <StatusBadge status={offer.state} />
                            <span>
                                {offer.issued_count}
                                {offer.voucher_limit !== null &&
                                    ` of ${offer.voucher_limit}`}{' '}
                                issued
                            </span>
                        </div>
                    </div>
                    {can.update &&
                        (offer.status === 'active' ? (
                            <ActionButton
                                form={pause.form(offer.id)}
                                variant="outline"
                            >
                                Pause
                            </ActionButton>
                        ) : (
                            <ActionButton form={activate.form(offer.id)}>
                                Activate
                            </ActionButton>
                        ))}
                </div>

                <PageErrors except={[...offerFieldNames, 'outlet_ids']} />

                {offer.hidden_reason !== null && (
                    <Notice title="Hidden by an admin">
                        Guests cannot claim or use this offer. It can run again
                        once an admin restores it.
                    </Notice>
                )}

                <Form
                    {...update.form(offer.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <fieldset
                            disabled={!can.update}
                            className="space-y-6 disabled:opacity-60"
                        >
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

                <Form
                    {...updateOutlets.form(offer.id)}
                    options={{ preserveScroll: true }}
                    className="border-t pt-6"
                >
                    {({ processing, errors }) => (
                        <fieldset
                            disabled={!can.update}
                            className="space-y-4 disabled:opacity-60"
                        >
                            <OfferOutletsField
                                outletOptions={outletOptions}
                                defaultOutletIds={ownOutletIds}
                                error={errors.outlet_ids}
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
            </div>
        </>
    );
}
