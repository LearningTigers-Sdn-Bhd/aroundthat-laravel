import { Deferred, Head, setLayoutProps } from '@inertiajs/react';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import UserPage from '@/components/admin/users/user-page';
import Heading from '@/components/heading';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/users';
import { index as activityIndex } from '@/routes/admin/users/activity';

type Props = {
    user: App.Data.Admin.UserData;
    activities?: App.Data.Admin.ActivityData[];
};

export default function UserActivity({ user, activities }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Users', href: index() },
            { title: user.name, href: show(user.id) },
            { title: 'Activity', href: activityIndex(user.id) },
        ],
    });

    return (
        <>
            <Head title={`${user.name} activity`} />

            <UserPage user={user}>
                <Heading
                    variant="small"
                    title="Activity"
                    description="The latest 100 changes to this login. Passwords are never logged."
                />
                <Deferred data="activities" fallback={<ActivitySkeleton />}>
                    <ActivityTimeline activities={activities ?? []} />
                </Deferred>
            </UserPage>
        </>
    );
}
