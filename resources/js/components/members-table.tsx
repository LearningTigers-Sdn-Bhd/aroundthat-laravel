import { Users } from 'lucide-react';
import type { ReactNode } from 'react';
import StatusBadge from '@/components/status-badge';
import {
    Empty,
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

type Props = {
    members: App.Data.MemberData[];
    /** Adds an actions column with what this returns for each member. */
    actions?: (member: App.Data.MemberData) => ReactNode;
};

/**
 * The members of a business with their role, outlets and status.
 */
export default function MembersTable({ members, actions }: Props) {
    if (members.length === 0) {
        return (
            <Empty className="border p-6">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Users />
                    </EmptyMedia>
                    <EmptyTitle>No members yet</EmptyTitle>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Member</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead>Outlets</TableHead>
                        <TableHead>Status</TableHead>
                        {actions && (
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        )}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {members.map((member) => (
                        <TableRow key={member.id}>
                            <TableCell>
                                <p className="font-medium">{member.name}</p>
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
                                          .map((outlet) => outlet.name)
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
                            {actions && (
                                <TableCell>{actions(member)}</TableCell>
                            )}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
