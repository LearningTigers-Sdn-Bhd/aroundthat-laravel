import { Deferred, Head, setLayoutProps } from '@inertiajs/react';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import BusinessPage from '@/components/admin/businesses/business-page';
import ChangeActions from '@/components/admin/change-actions';
import Heading from '@/components/heading';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/businesses';
import { index as activityIndex } from '@/routes/admin/businesses/activity';

type Props = {
    business: App.Data.Admin.BusinessData;
    activities?: App.Data.Admin.ActivityData[];
};

export default function BusinessActivity({ business, activities }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Businesses', href: index() },
            { title: business.name, href: show(business.id) },
            { title: 'Activity', href: activityIndex(business.id) },
        ],
    });

    return (
        <>
            <Head title={`${business.name} activity`} />

            <BusinessPage business={business}>
                <Heading
                    variant="small"
                    title="Activity"
                    description="The latest 100 changes to the business, its outlets, members and invitations."
                />
                <Deferred data="activities" fallback={<ActivitySkeleton />}>
                    <ActivityTimeline
                        activities={activities ?? []}
                        actions={(activity) => (
                            <ChangeActions activity={activity} />
                        )}
                    />
                </Deferred>
            </BusinessPage>
        </>
    );
}
