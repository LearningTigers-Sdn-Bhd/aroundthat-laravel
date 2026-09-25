import type { ReactNode } from 'react';
import BusinessActions from '@/components/admin/businesses/business-actions';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import HeaderTabLayout from '@/layouts/header-tab-layout';
import { formatDate, formatDateTime } from '@/lib/format';
import { show } from '@/routes/admin/businesses';
import { index as activity } from '@/routes/admin/businesses/activity';
import { index as members } from '@/routes/admin/businesses/members';
import { index as outlets } from '@/routes/admin/businesses/outlets';
import type { NavItem } from '@/types';

type Props = {
    business: App.Data.Admin.BusinessData;
    children: ReactNode;
};

/**
 * Every admin business tab: its name and state, the review and suspension actions, and the tabs.
 */
export default function BusinessPage({ business, children }: Props) {
    const tabs: NavItem[] = [
        { title: 'Details', href: show(business.id) },
        { title: 'Outlets', href: outlets(business.id) },
        { title: 'Members', href: members(business.id) },
        { title: 'Activity', href: activity(business.id) },
    ];

    return (
        <HeaderTabLayout
            title={business.name}
            badges={<StatusBadge status={recordStatus(business)} />}
            description={`Created ${formatDate(business.created_at)}`}
            actions={<BusinessActions business={business} />}
            tabs={tabs}
            tabsLabel="Business sections"
        >
            <PageErrors />

            {business.suspended_at && (
                <Notice title="Suspended">
                    {formatDateTime(business.suspended_at)} by{' '}
                    {business.suspended_by_name ?? 'an admin'}:{' '}
                    {business.suspension_reason}
                </Notice>
            )}
            {business.onboarding_status === 'rejected' && (
                <Notice title="Rejected">{business.rejection_reason}</Notice>
            )}

            {children}
        </HeaderTabLayout>
    );
}
