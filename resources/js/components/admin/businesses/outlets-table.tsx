import { Link } from '@inertiajs/react';
import StatusBadge, { recordStatus } from '@/components/status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { show } from '@/routes/admin/outlets';

type Props = {
    outlets: App.Data.Admin.OutletData[];
};

/**
 * A business's outlets, each linking to its admin page.
 */
export default function OutletsTable({ outlets }: Props) {
    if (outlets.length === 0) {
        return <p className="text-sm text-muted-foreground">No outlets yet.</p>;
    }

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Outlet</TableHead>
                        <TableHead>City</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Inside</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {outlets.map((outlet) => (
                        <TableRow key={outlet.id}>
                            <TableCell className="font-medium">
                                <Link
                                    href={show(outlet.id)}
                                    className="hover:underline"
                                >
                                    {outlet.name}
                                </Link>
                            </TableCell>
                            <TableCell>{outlet.city}</TableCell>
                            <TableCell>
                                <StatusBadge status={recordStatus(outlet)} />
                            </TableCell>
                            <TableCell>
                                {outlet.host_outlet?.name ?? '—'}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
