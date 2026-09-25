import { Head, setLayoutProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import BusinessPage from '@/components/admin/businesses/business-page';
import OutletsTable from '@/components/admin/businesses/outlets-table';
import ModalButtonLink from '@/components/modal-button-link';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/businesses';
import {
    create as createOutlet,
    index as outletsIndex,
} from '@/routes/admin/businesses/outlets';

type Props = {
    business: App.Data.Admin.BusinessData;
    outlets: App.Data.Admin.OutletData[];
};

export default function BusinessOutlets({ business, outlets }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Businesses', href: index() },
            { title: business.name, href: show(business.id) },
            { title: 'Outlets', href: outletsIndex(business.id) },
        ],
    });

    return (
        <>
            <Head title={`${business.name} outlets`} />

            <BusinessPage business={business}>
                {!business.suspended_at && (
                    <div className="flex justify-end">
                        <ModalButtonLink
                            variant="outline"
                            size="sm"
                            href={createOutlet(business.id).url}
                        >
                            <Plus />
                            Add outlet
                        </ModalButtonLink>
                    </div>
                )}
                <OutletsTable outlets={outlets} />
            </BusinessPage>
        </>
    );
}
