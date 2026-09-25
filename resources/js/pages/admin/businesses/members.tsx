import { Head, setLayoutProps } from '@inertiajs/react';
import BusinessPage from '@/components/admin/businesses/business-page';
import Heading from '@/components/heading';
import InvitationsTable from '@/components/invitations-table';
import MembersTable from '@/components/members-table';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/businesses';
import { index as membersIndex } from '@/routes/admin/businesses/members';
import { destroy, resend } from '@/routes/admin/invitations';

type Props = {
    business: App.Data.Admin.BusinessData;
    members: App.Data.MemberData[];
    invitations: App.Data.InvitationData[];
};

export default function BusinessMembers({
    business,
    members,
    invitations,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Businesses', href: index() },
            { title: business.name, href: show(business.id) },
            { title: 'Members', href: membersIndex(business.id) },
        ],
    });

    return (
        <>
            <Head title={`${business.name} members`} />

            <BusinessPage business={business}>
                <MembersTable members={members} />

                {invitations.length > 0 && (
                    <section className="space-y-3">
                        <Heading variant="small" title="Open invitations" />
                        <InvitationsTable
                            invitations={invitations}
                            resend={resend}
                            cancel={destroy}
                        />
                    </section>
                )}
            </BusinessPage>
        </>
    );
}
