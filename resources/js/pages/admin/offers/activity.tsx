import { Deferred, Head, setLayoutProps } from '@inertiajs/react';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import OfferPage from '@/components/admin/offers/offer-page';
import Heading from '@/components/heading';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/offers';
import { index as activityIndex } from '@/routes/admin/offers/activity';

type Props = {
    offer: App.Data.Admin.OfferData;
    activities?: App.Data.Admin.ActivityData[];
};

export default function OfferActivity({ offer: row, activities }: Props) {
    const { offer } = row;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Offers', href: index() },
            { title: offer.name, href: show(offer.id) },
            { title: 'Activity', href: activityIndex(offer.id) },
        ],
    });

    return (
        <>
            <Head title={`${offer.name} activity`} />

            <OfferPage offer={row}>
                <Heading
                    variant="small"
                    title="Activity"
                    description="The latest 100 changes to this offer."
                />
                <Deferred data="activities" fallback={<ActivitySkeleton />}>
                    <ActivityTimeline activities={activities ?? []} />
                </Deferred>
            </OfferPage>
        </>
    );
}
