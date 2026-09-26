import { Head, setLayoutProps } from '@inertiajs/react';
import { capabilities } from '@/components/admin/integrations/integration-fields';
import IntegrationPage from '@/components/admin/integrations/integration-page';
import Detail from '@/components/detail';
import Heading from '@/components/heading';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/integrations';

type Props = {
    integration: App.Data.Admin.IntegrationData;
};

export default function ShowIntegration({ integration }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Integrations', href: index() },
            { title: integration.name, href: show(integration.id) },
        ],
    });

    return (
        <>
            <Head title={integration.name} />

            <IntegrationPage integration={integration}>
                <section>
                    <Heading variant="small" title="Access" />
                    <dl className="mt-3 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                        <Detail label="Capabilities">
                            {integration.capabilities.length === 0
                                ? 'None'
                                : integration.capabilities
                                      .map(
                                          (capability) =>
                                              capabilities[
                                                  capability as App.Enums.IntegrationCapability
                                              ] ?? capability,
                                      )
                                      .join(', ')}
                        </Detail>
                        <Detail label="Outlets">All public outlets</Detail>
                        <Detail label="Keys work from">
                            {integration.starts_at
                                ? formatDateTime(integration.starts_at)
                                : 'Any time'}
                        </Detail>
                        <Detail label="Keys stop">
                            {integration.expires_at
                                ? formatDateTime(integration.expires_at)
                                : 'Never'}
                        </Detail>
                    </dl>
                </section>
            </IntegrationPage>
        </>
    );
}
