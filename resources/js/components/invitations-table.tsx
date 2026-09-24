import ActionButton from '@/components/action-button';
import StatusBadge from '@/components/status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatDateTime } from '@/lib/format';
import type { RouteFormDefinition } from '@/wayfinder';

/** A Wayfinder route that takes an invitation id, such as `resend`. */
type InvitationRoute = {
    form: (invitation: string) => RouteFormDefinition<'post'>;
};

type Props = {
    invitations: App.Data.InvitationData[];
    resend: InvitationRoute;
    cancel: InvitationRoute;
    showOutlets?: boolean;
};

/**
 * Open invitations to a business, with buttons to resend or cancel each one.
 */
export default function InvitationsTable({
    invitations,
    resend,
    cancel,
    showOutlets = false,
}: Props) {
    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Email</TableHead>
                        <TableHead>Role</TableHead>
                        {showOutlets && <TableHead>Outlets</TableHead>}
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
                            {showOutlets && (
                                <TableCell className="whitespace-normal">
                                    {invitation.role === 'owner'
                                        ? 'All outlets'
                                        : invitation.outlets
                                              .map((outlet) => outlet.name)
                                              .join(', ')}
                                </TableCell>
                            )}
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
                                        form={cancel.form(invitation.id)}
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
