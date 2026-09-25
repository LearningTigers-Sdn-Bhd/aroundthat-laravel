import { Deferred, Head, setLayoutProps, usePage } from '@inertiajs/react';
import { Check, Copy, KeyRound, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import ActionButton from '@/components/action-button';
import ActivityTimeline, {
    ActivitySkeleton,
} from '@/components/activity-timeline';
import IntegrationFields, {
    capabilities,
    integrationTypes,
} from '@/components/admin/integrations/integration-fields';
import ConfirmDialog from '@/components/confirm-dialog';
import Detail from '@/components/detail';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import Notice from '@/components/notice';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useClipboard } from '@/hooks/use-clipboard';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import {
    index,
    reactivate,
    show,
    suspend,
    update,
} from '@/routes/admin/integrations';
import { destroy, rotate, store } from '@/routes/admin/integrations/keys';

type Props = {
    integration: App.Data.Admin.IntegrationData;
    keys: App.Data.Admin.ApiKeyData[];
    activities?: App.Data.Admin.ActivityData[];
};

type NewKey = { name: string; key: string };

export default function ShowIntegration({
    integration,
    keys,
    activities,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Integrations', href: index() },
            { title: integration.name, href: show(integration.id) },
        ],
    });

    // Flash data is gone on the next request, such as the deferred activity load, so keep the key in state.
    const flashedKey = usePage().flash.api_key as NewKey | undefined;
    const [newKey, setNewKey] = useState(flashedKey);

    useEffect(() => {
        if (flashedKey) {
            setNewKey(flashedKey);
        }
    }, [flashedKey]);

    return (
        <>
            <Head title={integration.name} />

            <div className="flex flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold tracking-tight">
                                {integration.name}
                            </h1>
                            <StatusBadge
                                status={
                                    integration.suspended_at
                                        ? 'suspended'
                                        : integration.is_usable
                                          ? 'active'
                                          : 'expired'
                                }
                            />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {integrationTypes[integration.type]}
                        </p>
                    </div>

                    <div className="flex gap-2">
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
                                    <Button variant="destructive">
                                        Suspend
                                    </Button>
                                }
                                title={`Suspend ${integration.name}?`}
                                description="Every API key of this integration stops working until it is reactivated."
                                form={suspend.form(integration.id)}
                                submitLabel="Suspend"
                                destructive
                            />
                        )}
                    </div>
                </div>

                <PageErrors />

                {integration.suspended_at && (
                    <Notice title="Suspended">
                        {formatDateTime(integration.suspended_at)} by{' '}
                        {integration.suspended_by_name ?? 'an admin'}:{' '}
                        {integration.suspension_reason}
                    </Notice>
                )}

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

                <section className="space-y-3">
                    <div className="flex items-start justify-between gap-4">
                        <Heading
                            variant="small"
                            title="API keys"
                            description="Partners send a key as a bearer token. A key is shown once, when it is created."
                        />
                        <FormDialog
                            trigger={
                                <Button variant="outline">
                                    <Plus />
                                    New key
                                </Button>
                            }
                            title="New API key"
                            form={store.form(integration.id)}
                            submitLabel="Create"
                        >
                            {(errors) => (
                                <>
                                    <TextField
                                        name="name"
                                        label="Name"
                                        placeholder="Production server"
                                        maxLength={80}
                                        error={errors.name}
                                        required
                                    />
                                    <TextField
                                        name="expires_on"
                                        label="Stops working on (optional)"
                                        type="date"
                                        error={errors.expires_on}
                                    />
                                </>
                            )}
                        </FormDialog>
                    </div>

                    {newKey && <NewKeyNotice newKey={newKey} />}

                    {keys.length === 0 ? (
                        <p className="flex items-center gap-2 text-sm text-muted-foreground">
                            <KeyRound className="size-4" />
                            No API keys yet.
                        </p>
                    ) : (
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Created</TableHead>
                                        <TableHead>Last used</TableHead>
                                        <TableHead>Expires</TableHead>
                                        <TableHead className="w-48" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {keys.map((key) => (
                                        <TableRow key={key.id}>
                                            <TableCell className="font-medium">
                                                {key.name}
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(key.created_at)}
                                            </TableCell>
                                            <TableCell>
                                                {key.last_used_at
                                                    ? formatDateTime(
                                                          key.last_used_at,
                                                      )
                                                    : 'Never'}
                                            </TableCell>
                                            <TableCell>
                                                {key.expires_at ? (
                                                    <span
                                                        className={
                                                            key.is_expired
                                                                ? 'text-muted-foreground line-through'
                                                                : undefined
                                                        }
                                                    >
                                                        {formatDate(
                                                            key.expires_at,
                                                        )}
                                                    </span>
                                                ) : (
                                                    'Never'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex justify-end gap-2">
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                            >
                                                                Rotate
                                                            </Button>
                                                        }
                                                        title={`Rotate ${key.name}?`}
                                                        description="A new key replaces this one. The current key stops working at once."
                                                        form={rotate.form({
                                                            integration:
                                                                integration.id,
                                                            key: key.id,
                                                        })}
                                                        confirmLabel="Rotate"
                                                    />
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                            >
                                                                Revoke
                                                            </Button>
                                                        }
                                                        title={`Revoke ${key.name}?`}
                                                        description="The key stops working at once. This cannot be undone."
                                                        form={destroy.form({
                                                            integration:
                                                                integration.id,
                                                            key: key.id,
                                                        })}
                                                        confirmLabel="Revoke"
                                                        destructive
                                                    />
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title="Activity"
                        description="The latest 100 changes to this integration and its keys."
                    />
                    <Deferred data="activities" fallback={<ActivitySkeleton />}>
                        <ActivityTimeline activities={activities ?? []} />
                    </Deferred>
                </section>
            </div>
        </>
    );
}

function NewKeyNotice({ newKey }: { newKey: NewKey }) {
    const [copied, copy] = useClipboard();

    return (
        <div className="space-y-2 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            <p className="font-medium">
                Copy the key for {newKey.name} now. It is not shown again.
            </p>
            <div className="flex items-center gap-2">
                <code className="min-w-0 flex-1 truncate rounded bg-background px-2 py-1 font-mono text-foreground">
                    {newKey.key}
                </code>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => void copy(newKey.key)}
                >
                    {copied === newKey.key ? <Check /> : <Copy />}
                    {copied === newKey.key ? 'Copied' : 'Copy'}
                </Button>
            </div>
        </div>
    );
}
