import { Building2, FileText, History, LifeBuoy } from 'lucide-react';
import type { ReactNode } from 'react';
import ActionButton from '@/components/action-button';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import HeaderTabLayout from '@/layouts/header-tab-layout';
import { formatDateTime } from '@/lib/format';
import { reactivate, show, suspend } from '@/routes/admin/users';
import { index as activity } from '@/routes/admin/users/activity';
import { index as businesses } from '@/routes/admin/users/businesses';
import { index as recovery } from '@/routes/admin/users/recovery';
import type { NavItem } from '@/types';

type Props = {
    user: App.Data.Admin.UserData;
    /** Fields whose errors the tab already shows next to the input. */
    errorsShownInForms?: string[];
    children: ReactNode;
};

/**
 * Every admin user tab: the login's name and state, the suspension actions, and the tabs.
 */
export default function UserPage({
    user,
    errorsShownInForms = [],
    children,
}: Props) {
    const tabs: NavItem[] = [
        { title: 'Details', href: show(user.id), icon: FileText },
        { title: 'Businesses', href: businesses(user.id), icon: Building2 },
        { title: 'Recovery', href: recovery(user.id), icon: LifeBuoy },
        { title: 'Activity', href: activity(user.id), icon: History },
    ];

    return (
        <HeaderTabLayout
            title={user.name}
            badges={
                <>
                    {user.is_admin && <Badge variant="secondary">Admin</Badge>}
                    <StatusBadge
                        status={user.suspended_at ? 'suspended' : 'active'}
                    />
                </>
            }
            description={user.email}
            actions={
                user.suspended_at ? (
                    <ActionButton
                        form={reactivate.form(user.id)}
                        variant="outline"
                    >
                        Reactivate
                    </ActionButton>
                ) : (
                    <ReasonDialog
                        trigger={<Button variant="destructive">Suspend</Button>}
                        title={`Suspend ${user.name}?`}
                        description="They are logged out and cannot log in to any business until reactivated."
                        form={suspend.form(user.id)}
                        submitLabel="Suspend"
                        destructive
                    />
                )
            }
            tabs={tabs}
            tabsLabel="User sections"
        >
            <PageErrors except={errorsShownInForms} />

            {user.suspended_at && (
                <Notice title="Suspended">
                    {formatDateTime(user.suspended_at)} by{' '}
                    {user.suspended_by_name ?? 'an admin'}:{' '}
                    {user.suspension_reason}
                </Notice>
            )}

            {children}
        </HeaderTabLayout>
    );
}
