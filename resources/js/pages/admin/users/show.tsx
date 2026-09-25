import { Head, setLayoutProps } from '@inertiajs/react';
import UserPage from '@/components/admin/users/user-page';
import Detail from '@/components/detail';
import Heading from '@/components/heading';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/users';

type Props = {
    user: App.Data.Admin.UserData;
};

export default function ShowUser({ user }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Users', href: index() },
            { title: user.name, href: show(user.id) },
        ],
    });

    return (
        <>
            <Head title={user.name} />

            <UserPage user={user}>
                <section>
                    <Heading variant="small" title="Account" />
                    <dl className="mt-3 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                        <Detail label="Email verified">
                            {user.is_email_verified ? 'Yes' : 'No'}
                        </Detail>
                        <Detail label="Two-factor authentication">
                            {user.has_two_factor ? 'On' : 'Off'}
                        </Detail>
                        <Detail label="Temporary password">
                            {user.must_change_password
                                ? 'Must change it at next login'
                                : 'No'}
                        </Detail>
                        <Detail label="Last login">
                            {formatDateTime(user.last_login_at)}
                        </Detail>
                        <Detail label="Joined">
                            {formatDate(user.created_at)}
                        </Detail>
                    </dl>
                </section>
            </UserPage>
        </>
    );
}
