import { Deferred, Head, setLayoutProps } from '@inertiajs/react';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import IntegrationPage from '@/components/admin/integrations/integration-page';
import Heading from '@/components/heading';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/integrations';
import { index as activityIndex } from '@/routes/admin/integrations/activity';

type Props = {
    integration: App.Data.Admin.IntegrationData;
    activities?: App.Data.Admin.ActivityData[];
};

export default function IntegrationActivity({
    integration,
    activities,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Integrations', href: index() },
            { title: integration.name, href: show(integration.id) },
            { title: 'Activity', href: activityIndex(integration.id) },
        ],
    });

    return (
        <>
            <Head title={`${integration.name} activity`} />

            <IntegrationPage integration={integration}>
                <Heading
                    variant="small"
                    title="Activity"
                    description="The latest 100 changes to this integration and its keys."
                />
                <Deferred data="activities" fallback={<ActivitySkeleton />}>
                    <ActivityTimeline activities={activities ?? []} />
                </Deferred>
            </IntegrationPage>
        </>
    );
}
