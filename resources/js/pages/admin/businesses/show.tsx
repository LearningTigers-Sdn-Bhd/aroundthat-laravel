import { Head, setLayoutProps } from '@inertiajs/react';
import BusinessPage from '@/components/admin/businesses/business-page';
import Detail from '@/components/detail';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/businesses';

type Props = {
    business: App.Data.Admin.BusinessData;
};

export default function ShowBusiness({ business }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Businesses', href: index() },
            { title: business.name, href: show(business.id) },
        ],
    });

    return (
        <>
            <Head title={business.name} />

            <BusinessPage business={business}>
                <dl className="grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                    <Detail label="Registered name">
                        {business.registered_name}
                    </Detail>
                    <Detail label="Registration number">
                        {business.registration_number}
                    </Detail>
                    <Detail label="Contact email">
                        {business.contact_email}
                    </Detail>
                    <Detail label="Contact phone">
                        {business.contact_phone}
                    </Detail>
                    <Detail label="Address">{business.address}</Detail>
                    <Detail label="Timezone">{business.timezone}</Detail>
                    <Detail label="Submitted">
                        {formatDateTime(business.submitted_at)}
                    </Detail>
                    <Detail label="Approved">
                        {business.approved_at
                            ? `${formatDateTime(business.approved_at)} by ${business.approved_by_name ?? 'an admin'}`
                            : null}
                    </Detail>
                </dl>
            </BusinessPage>
        </>
    );
}
