import { Deferred, Head, Link, setLayoutProps } from '@inertiajs/react';
import OfferOutlets from '@/components/admin/offers/offer-outlets';
import ActionButton from '@/components/action-button';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import Detail from '@/components/detail';
import Heading from '@/components/heading';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { discountSummary } from '@/lib/offers';
import { dashboard } from '@/routes/admin';
import { show as showBusiness } from '@/routes/admin/businesses';
import { hide, index, show, unhide } from '@/routes/admin/offers';

type Props = {
    offer: App.Data.Admin.OfferData;
    sponsorCandidates: App.Data.Admin.HostOutletOptionData[];
    activities?: App.Data.Admin.ActivityData[];
};

export default function ShowOffer({
    offer: row,
    sponsorCandidates,
    activities,
}: Props) {
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

            <div className="flex flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {offer.name}
                            </h1>
                            <StatusBadge status={offer.state} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Offer of{' '}
                            <Link
                                href={showBusiness(row.business_id)}
                                className="hover:underline"
                            >
                                {row.business_name}
                            </Link>
                            {' · '}
                            {discountSummary(offer)}
                        </p>
                    </div>

                    {row.hidden_at ? (
                        <ActionButton
                            form={unhide.form(offer.id)}
                            variant="outline"
                        >
                            Restore
                        </ActionButton>
                    ) : (
                        <ReasonDialog
                            trigger={<Button variant="outline">Hide</Button>}
                            title={`Hide ${offer.name}?`}
                            description="Guests can no longer claim it and issued vouchers cannot be used until you restore it. The owners get an email with your reason."
                            form={hide.form(offer.id)}
                            submitLabel="Hide"
                            destructive
                        />
                    )}
                </div>

                <PageErrors except={['outlet_id']} />

                {row.hidden_at && (
                    <Notice title="Hidden">
                        {formatDateTime(row.hidden_at)}: {offer.hidden_reason}
                    </Notice>
                )}

                <section>
                    <Heading variant="small" title="Terms" />
                    <dl className="mt-3 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                        <Detail label="Description">{offer.description}</Detail>
                        <Detail label="Discount">
                            {discountSummary(offer)}
                        </Detail>
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
                </section>

                <OfferOutlets offer={offer} candidates={sponsorCandidates} />

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title="Activity"
                        description="The latest 100 changes to this offer."
                    />
                    <Deferred data="activities" fallback={<ActivitySkeleton />}>
                        <ActivityTimeline activities={activities ?? []} />
                    </Deferred>
                </section>
            </div>
        </>
    );
}
