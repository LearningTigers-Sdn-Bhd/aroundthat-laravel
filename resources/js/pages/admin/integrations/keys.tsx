import { Head, setLayoutProps, usePage } from '@inertiajs/react';
import { Check, Copy, KeyRound, Plus } from 'lucide-react';
import IntegrationPage from '@/components/admin/integrations/integration-page';
import ConfirmDialog from '@/components/confirm-dialog';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
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
import { index, show } from '@/routes/admin/integrations';
import {
    destroy,
    index as keysIndex,
    rotate,
    store,
} from '@/routes/admin/integrations/keys';

type Props = {
    integration: App.Data.Admin.IntegrationData;
    keys: App.Data.Admin.ApiKeyData[];
};

type NewKey = { name: string; key: string };

export default function IntegrationKeys({ integration, keys }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Integrations', href: index() },
            { title: integration.name, href: show(integration.id) },
            { title: 'API keys', href: keysIndex(integration.id) },
        ],
    });

    const newKey = usePage().flash.api_key as NewKey | undefined;

    return (
        <>
            <Head title={`${integration.name} API keys`} />

            <IntegrationPage integration={integration}>
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
                        <Empty className="border p-6">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <KeyRound />
                                </EmptyMedia>
                                <EmptyTitle>No API keys yet</EmptyTitle>
                                <EmptyDescription>
                                    Create a key and send it to the partner.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    ) : (
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Created</TableHead>
                                        <TableHead>Last used</TableHead>
                                        <TableHead>Expires</TableHead>
                                        <TableHead className="w-48">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
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
            </IntegrationPage>
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
