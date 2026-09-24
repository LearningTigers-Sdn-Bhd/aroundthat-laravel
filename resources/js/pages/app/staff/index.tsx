import { Form, Head, usePage } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import ActionButton from '@/components/action-button';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PageErrors from '@/components/page-errors';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatDateTime } from '@/lib/format';
import { destroy, index, reactivate, suspend, update } from '@/routes/staff';
import {
    destroy as cancelInvitation,
    resend,
    store as invite,
} from '@/routes/staff/invitations';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    members: App.Data.MemberData[];
    invitations: App.Data.InvitationData[];
    outletOptions: App.Data.OutletOptionData[];
    roles: App.Enums.MembershipRole[];
    canInvite: boolean;
};

const roleHelp: Record<App.Enums.MembershipRole, string> = {
    owner: 'Runs the business: details, outlets, staff, offers and reports at every outlet.',
    manager: 'Runs offers, scans vouchers and sees reports at their outlets.',
    cashier: 'Scans vouchers at their outlets.',
};

export default function StaffIndex({
    members,
    invitations,
    outletOptions,
    roles,
    canInvite,
}: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Staff" />

            <div className="flex flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Staff"
                        description="Who works in this business, what they can do, and at which outlets."
                    />
                    {canInvite && (
                        <InviteDialog
                            roles={roles}
                            outletOptions={outletOptions}
                        />
                    )}
                </div>

                <PageErrors
                    except={['email', 'role', 'outlet_ids', 'reason']}
                />

                <section className="space-y-3">
                    <Heading variant="small" title="Members" />
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Member</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Outlets</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {members.map((member) => (
                                    <TableRow key={member.id}>
                                        <TableCell>
                                            <p className="font-medium">
                                                {member.name}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {member.email}
                                            </p>
                                        </TableCell>
                                        <TableCell className="capitalize">
                                            {member.role}
                                        </TableCell>
                                        <TableCell className="whitespace-normal">
                                            {member.role === 'owner'
                                                ? 'All outlets'
                                                : member.outlets
                                                      .map(
                                                          (outlet) =>
                                                              outlet.name,
                                                      )
                                                      .join(', ')}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                status={
                                                    member.suspended_at
                                                        ? 'suspended'
                                                        : 'active'
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            {member.user_id === auth.user.id ? (
                                                <p className="text-right text-muted-foreground">
                                                    You
                                                </p>
                                            ) : (
                                                <MemberActions
                                                    member={member}
                                                    roles={roles}
                                                    outletOptions={
                                                        outletOptions
                                                    }
                                                />
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </section>

                {invitations.length > 0 && (
                    <section className="space-y-3">
                        <Heading variant="small" title="Open invitations" />
                        <InvitationsTable invitations={invitations} />
                    </section>
                )}
            </div>
        </>
    );
}

function MemberActions({
    member,
    roles,
    outletOptions,
}: {
    member: App.Data.MemberData;
    roles: App.Enums.MembershipRole[];
    outletOptions: App.Data.OutletOptionData[];
}) {
    return (
        <div className="flex justify-end gap-2">
            <FormDialog
                trigger={
                    <Button variant="outline" size="sm">
                        Change access
                    </Button>
                }
                title={`Change ${member.name}'s access`}
                form={update.form(member.id)}
                submitLabel="Save"
            >
                {(errors) => (
                    <RoleAndOutletsFields
                        roles={roles}
                        outletOptions={outletOptions}
                        defaultRole={member.role}
                        defaultOutletIds={member.outlets.map(
                            (outlet) => outlet.id,
                        )}
                        errors={errors}
                    />
                )}
            </FormDialog>

            {member.suspended_at ? (
                <ActionButton
                    form={reactivate.form(member.id)}
                    variant="outline"
                    size="sm"
                >
                    Reactivate
                </ActionButton>
            ) : (
                <ReasonDialog
                    trigger={
                        <Button variant="outline" size="sm">
                            Suspend
                        </Button>
                    }
                    title={`Suspend ${member.name}?`}
                    description="They keep their login but cannot work in this business until you reactivate them."
                    form={suspend.form(member.id)}
                    submitLabel="Suspend"
                    destructive
                />
            )}

            <FormDialog
                trigger={
                    <Button variant="ghost" size="sm">
                        Remove
                    </Button>
                }
                title={`Remove ${member.name}?`}
                description="They lose access to this business. Invite them again to bring them back."
                form={destroy.form(member.id)}
                submitLabel="Remove"
                destructive
            />
        </div>
    );
}

function InviteDialog({
    roles,
    outletOptions,
}: {
    roles: App.Enums.MembershipRole[];
    outletOptions: App.Data.OutletOptionData[];
}) {
    return (
        <FormDialog
            trigger={
                <Button>
                    <UserPlus />
                    Invite
                </Button>
            }
            title="Invite staff"
            description="We email them a link to join. It works for 14 days."
            form={invite.form()}
            submitLabel="Send invitation"
        >
            {(errors) => (
                <>
                    <TextField
                        name="email"
                        label="Email"
                        type="email"
                        error={errors.email}
                        required
                    />
                    <RoleAndOutletsFields
                        roles={roles}
                        outletOptions={outletOptions}
                        defaultRole="cashier"
                        defaultOutletIds={[]}
                        errors={errors}
                    />
                </>
            )}
        </FormDialog>
    );
}

/**
 * A role, and for managers and cashiers the outlets they work at.
 */
function RoleAndOutletsFields({
    roles,
    outletOptions,
    defaultRole,
    defaultOutletIds,
    errors,
}: {
    roles: App.Enums.MembershipRole[];
    outletOptions: App.Data.OutletOptionData[];
    defaultRole: App.Enums.MembershipRole;
    defaultOutletIds: string[];
    errors: Record<string, string>;
}) {
    const [role, setRole] = useState(defaultRole);

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="role">Role</Label>
                <Select
                    name="role"
                    value={role}
                    onValueChange={(value) =>
                        setRole(value as App.Enums.MembershipRole)
                    }
                >
                    <SelectTrigger id="role" className="capitalize">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {roles.map((option) => (
                            <SelectItem
                                key={option}
                                value={option}
                                className="capitalize"
                            >
                                {option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <p className="text-sm text-muted-foreground">
                    {roleHelp[role]}
                </p>
                <InputError message={errors.role} />
            </div>

            {role !== 'owner' && (
                <fieldset className="grid gap-2">
                    <legend className="mb-2 text-sm font-medium">
                        Outlets
                    </legend>
                    {outletOptions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No approved, active outlets yet.
                        </p>
                    ) : (
                        outletOptions.map((outlet) => (
                            <div
                                key={outlet.id}
                                className="flex items-center gap-3"
                            >
                                <Checkbox
                                    id={`outlet-${outlet.id}`}
                                    name="outlet_ids[]"
                                    value={outlet.id}
                                    defaultChecked={defaultOutletIds.includes(
                                        outlet.id,
                                    )}
                                />
                                <Label htmlFor={`outlet-${outlet.id}`}>
                                    {outlet.name}
                                </Label>
                            </div>
                        ))
                    )}
                    <InputError message={errors.outlet_ids} />
                </fieldset>
            )}
        </>
    );
}

/**
 * A dialog holding a small form, such as an invitation or a confirmation with no fields.
 */
function FormDialog({
    trigger,
    title,
    description,
    form,
    submitLabel,
    destructive = false,
    children,
}: {
    trigger: ReactNode;
    title: string;
    description?: string;
    form: RouteFormDefinition<'post'>;
    submitLabel: string;
    destructive?: boolean;
    children?: (errors: Record<string, string>) => ReactNode;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                {description && (
                    <DialogDescription>{description}</DialogDescription>
                )}

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="space-y-6"
                >
                    {({ processing, errors, resetAndClearErrors }) => (
                        <>
                            {children?.(errors)}
                            {!children &&
                                Object.values(errors).map((message) => (
                                    <InputError
                                        key={message}
                                        message={message}
                                    />
                                ))}

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => resetAndClearErrors()}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant={
                                        destructive ? 'destructive' : 'default'
                                    }
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {submitLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function InvitationsTable({
    invitations,
}: {
    invitations: App.Data.InvitationData[];
}) {
    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Email</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead>Outlets</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Sent</TableHead>
                        <TableHead>Expires</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {invitations.map((invitation) => (
                        <TableRow key={invitation.id}>
                            <TableCell className="font-medium">
                                {invitation.email}
                            </TableCell>
                            <TableCell className="capitalize">
                                {invitation.role}
                            </TableCell>
                            <TableCell className="whitespace-normal">
                                {invitation.role === 'owner'
                                    ? 'All outlets'
                                    : invitation.outlets
                                          .map((outlet) => outlet.name)
                                          .join(', ')}
                            </TableCell>
                            <TableCell>
                                <StatusBadge status={invitation.status} />
                            </TableCell>
                            <TableCell>
                                {invitation.sent_at
                                    ? formatDateTime(invitation.sent_at)
                                    : 'Sending…'}
                            </TableCell>
                            <TableCell>
                                {formatDate(invitation.expires_at)}
                            </TableCell>
                            <TableCell>
                                <div className="flex justify-end gap-2">
                                    <ActionButton
                                        form={resend.form(invitation.id)}
                                        variant="outline"
                                        size="sm"
                                    >
                                        Resend
                                    </ActionButton>
                                    <ActionButton
                                        form={cancelInvitation.form(
                                            invitation.id,
                                        )}
                                        variant="ghost"
                                        size="sm"
                                    >
                                        Cancel
                                    </ActionButton>
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

StaffIndex.layout = {
    breadcrumbs: [{ title: 'Staff', href: index() }],
};
