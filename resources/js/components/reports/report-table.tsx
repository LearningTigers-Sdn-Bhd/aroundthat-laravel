import { ShieldCheck } from 'lucide-react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatReportValue } from '@/lib/reports';
import type { ReportValue } from '@/lib/reports';
import { cn } from '@/lib/utils';

type Props = {
    columns: App.Support.Reports.ReportColumn[];
    rows: Record<string, ReportValue>[];
    currency: string;
};

/**
 * A report's rows. The first column names the row; numbers sit on the right. A row the privacy guard hid keeps its
 * name and says why its numbers are missing.
 */
export default function ReportTable({ columns, rows, currency }: Props) {
    const [first, ...measures] = columns;

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        {columns.map((column) => (
                            <TableHead
                                key={column.key}
                                className={cn(
                                    column.format !== 'text' && 'text-right',
                                )}
                            >
                                {column.label}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.map((row) => (
                        <TableRow key={String(row.key)}>
                            <TableCell className="font-medium">
                                {String(row[first.key] ?? '')}
                            </TableCell>
                            {row.hidden ? (
                                <TableCell
                                    colSpan={measures.length}
                                    className="text-muted-foreground"
                                >
                                    <span className="inline-flex items-center gap-1.5">
                                        <ShieldCheck className="size-4" />
                                        Fewer than 5 — hidden for privacy
                                    </span>
                                </TableCell>
                            ) : (
                                measures.map((column) => (
                                    <TableCell
                                        key={column.key}
                                        className={cn(
                                            'tabular-nums',
                                            column.format !== 'text' &&
                                                'text-right',
                                        )}
                                    >
                                        {formatReportValue(
                                            row[column.key],
                                            column.format,
                                            currency,
                                        )}
                                    </TableCell>
                                ))
                            )}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
