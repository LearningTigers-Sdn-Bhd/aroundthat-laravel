import { Users } from 'lucide-react';
import type { ReactNode } from 'react';
import type { DataTableColumn } from '@/components/data-table';
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

type MemberActions = (member: App.Data.MemberData) => ReactNode;

type Props = {
    members: App.Data.MemberData[];
    /** Adds an actions column with what this returns for each member. */
    actions?: MemberActions;
};

/**
 * The member columns: who they are, their role, outlets and status, and optionally an actions column.
 */
export function memberColumns(
    actions?: MemberActions,
): DataTableColumn<App.Data.MemberData>[] {
    return [
        {
            key: 'name',
            header: 'Member',
            sort: 'name',
            cell: (member) => (
                <>
                    <p className="font-medium">{member.name}</p>
                    <p className="text-muted-foreground">{member.email}</p>
                </>
            ),
        },
        {
            key: 'role',
            header: 'Role',
            className: 'capitalize',
            cell: (member) => member.role,
        },
        {
            key: 'outlets',
            header: 'Outlets',
            className: 'whitespace-normal',
            cell: (member) =>
                member.role === 'owner'
                    ? 'All outlets'
                    : member.outlets.map((outlet) => outlet.name).join(', '),
        },
        {
            key: 'status',
            header: 'Status',
            cell: (member) => (
                <StatusBadge
                    status={member.suspended_at ? 'suspended' : 'active'}
                />
            ),
        },
        ...(actions
            ? [
                  {
                      key: 'actions',
                      header: 'Actions',
                      className: 'text-right',
                      cell: actions,
                  },
              ]
            : []),
    ];
}

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

    const columns = memberColumns(actions);

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        {columns.map((column) => (
                            <TableHead
                                key={column.key}
                                className={column.className}
                            >
                                {column.header}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {members.map((member) => (
                        <TableRow key={member.id}>
                            {columns.map((column) => (
                                <TableCell
                                    key={column.key}
                                    className={column.className}
                                >
                                    {column.cell(member)}
                                </TableCell>
                            ))}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
