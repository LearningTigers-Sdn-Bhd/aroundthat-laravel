import { FileText, Store, Ticket } from 'lucide-react';
import type { ReactNode } from 'react';
import ActionButton from '@/components/action-button';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import StatusBadge from '@/components/status-badge';
import HeaderTabLayout from '@/layouts/header-tab-layout';
import { discountSummary } from '@/lib/offers';
import { activate, edit, pause } from '@/routes/offers';
import { index as outlets } from '@/routes/offers/outlets';
import { index as vouchers } from '@/routes/offers/vouchers';
import type { NavItem } from '@/types';

type Props = {
    offer: App.Data.OfferData;
    canUpdate: boolean;
    /** Fields whose errors the tab already shows next to the input. */
    errorsShownInForms?: string[];
    children: ReactNode;
};

/**
 * Every offer tab: its name, terms and state, the pause and activate actions, and the tabs.
 */
export default function OfferPage({
    offer,
    canUpdate,
    errorsShownInForms = [],
    children,
}: Props) {
    const tabs: NavItem[] = [
        { title: 'Details', href: edit(offer.id), icon: FileText },
        { title: 'Vouchers', href: vouchers(offer.id), icon: Ticket },
        { title: 'Outlets', href: outlets(offer.id), icon: Store },
    ];

    return (
        <HeaderTabLayout
            title={offer.name}
            badges={<StatusBadge status={offer.state} />}
            description={
                <>
                    {discountSummary(offer)}
                    {' · '}
                    {offer.issued_count}
                    {offer.voucher_limit !== null &&
                        ` of ${offer.voucher_limit}`}{' '}
                    issued
                </>
            }
            actions={
                canUpdate &&
                (offer.status === 'active' ? (
                    <ActionButton form={pause.form(offer.id)} variant="outline">
                        Pause
                    </ActionButton>
                ) : (
                    <ActionButton form={activate.form(offer.id)}>
                        Activate
                    </ActionButton>
                ))
            }
            tabs={tabs}
            tabsLabel="Offer sections"
        >
            <PageErrors except={errorsShownInForms} />

            {offer.hidden_reason !== null && (
                <Notice title="Hidden by an admin">
                    Guests cannot claim or use this offer. It can run again once
                    an admin restores it.
                </Notice>
            )}

            {children}
        </HeaderTabLayout>
    );
}
