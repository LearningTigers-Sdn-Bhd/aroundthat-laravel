import { Link } from '@inertiajs/react';
import { FileText, History, Store } from 'lucide-react';
import type { ReactNode } from 'react';
import ActionButton from '@/components/action-button';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import HeaderTabLayout from '@/layouts/header-tab-layout';
import { formatDateTime } from '@/lib/format';
import { discountSummary } from '@/lib/offers';
import { show as showBusiness } from '@/routes/admin/businesses';
import { hide, show, unhide } from '@/routes/admin/offers';
import { index as activity } from '@/routes/admin/offers/activity';
import { index as outlets } from '@/routes/admin/offers/outlets';
import type { NavItem } from '@/types';

type Props = {
    offer: App.Data.Admin.OfferData;
    /** Fields whose errors the tab already shows next to the input. */
    errorsShownInForms?: string[];
    children: ReactNode;
};

/**
 * Every admin offer tab: its name, business and state, the hide and restore actions, and the tabs.
 */
export default function OfferPage({
    offer: row,
    errorsShownInForms = [],
    children,
}: Props) {
    const { offer } = row;

    const tabs: NavItem[] = [
        { title: 'Terms', href: show(offer.id), icon: FileText },
        { title: 'Outlets', href: outlets(offer.id), icon: Store },
        { title: 'Activity', href: activity(offer.id), icon: History },
    ];

    return (
        <HeaderTabLayout
            title={offer.name}
            badges={<StatusBadge status={offer.state} />}
            description={
                <>
                    Offer of{' '}
                    <Link
                        href={showBusiness(row.business_id)}
                        className="hover:underline"
                    >
                        {row.business_name}
                    </Link>
                    {' · '}
                    {discountSummary(offer)}
                </>
            }
            actions={
                row.hidden_at ? (
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
                )
            }
            tabs={tabs}
            tabsLabel="Offer sections"
        >
            <PageErrors except={errorsShownInForms} />

            {row.hidden_at && (
                <Notice title="Hidden">
                    {formatDateTime(row.hidden_at)}: {offer.hidden_reason}
                </Notice>
            )}

            {children}
        </HeaderTabLayout>
    );
}
