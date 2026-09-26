import { History, KeyRound, ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import ActionButton from '@/components/action-button';
import IntegrationFields, {
    integrationTypes,
} from '@/components/admin/integrations/integration-fields';
import FormDialog from '@/components/form-dialog';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import HeaderTabLayout from '@/layouts/header-tab-layout';
import { formatDateTime } from '@/lib/format';
import { reactivate, show, suspend, update } from '@/routes/admin/integrations';
import { index as activity } from '@/routes/admin/integrations/activity';
import { index as keys } from '@/routes/admin/integrations/keys';
import type { NavItem } from '@/types';

type Props = {
    integration: App.Data.Admin.IntegrationData;
    children: ReactNode;
};

/**
 * Every admin integration tab: its name, type and state, the edit, suspend and reactivate actions, and the tabs.
 */
export default function IntegrationPage({ integration, children }: Props) {
    const tabs: NavItem[] = [
        { title: 'Access', href: show(integration.id), icon: ShieldCheck },
        { title: 'API keys', href: keys(integration.id), icon: KeyRound },
        { title: 'Activity', href: activity(integration.id), icon: History },
    ];

    return (
        <HeaderTabLayout
            title={integration.name}
            badges={
                <StatusBadge
                    status={
                        integration.suspended_at
                            ? 'suspended'
                            : integration.is_usable
                              ? 'active'
                              : 'expired'
                    }
                />
            }
            description={integrationTypes[integration.type]}
            actions={
                <>
                    <FormDialog
                        trigger={<Button variant="outline">Edit</Button>}
                        title={`Edit ${integration.name}`}
                        description="Capability changes apply to its existing keys at once."
                        form={update.form(integration.id)}
                        submitLabel="Save"
                    >
                        {(errors) => (
                            <IntegrationFields
                                errors={errors}
                                integration={integration}
                            />
                        )}
                    </FormDialog>
                    {integration.suspended_at ? (
                        <ActionButton
                            form={reactivate.form(integration.id)}
                            variant="outline"
                        >
                            Reactivate
                        </ActionButton>
                    ) : (
                        <ReasonDialog
                            trigger={
                                <Button variant="destructive">Suspend</Button>
                            }
                            title={`Suspend ${integration.name}?`}
                            description="Every API key of this integration stops working until it is reactivated."
                            form={suspend.form(integration.id)}
                            submitLabel="Suspend"
                            destructive
                        />
                    )}
                </>
            }
            tabs={tabs}
            tabsLabel="Integration sections"
        >
            <PageErrors />

            {integration.suspended_at && (
                <Notice title="Suspended">
                    {formatDateTime(integration.suspended_at)} by{' '}
                    {integration.suspended_by_name ?? 'an admin'}:{' '}
                    {integration.suspension_reason}
                </Notice>
            )}

            {children}
        </HeaderTabLayout>
    );
}
