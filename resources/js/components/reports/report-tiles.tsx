import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatReportValue } from '@/lib/reports';
import { cn } from '@/lib/utils';

type Props = {
    tiles: App.Support.Reports.ReportTile[];
    currency: string;
};

/** Wide screens get one column per tile, so three tiles fill the row as well as four. */
const wideColumns: Record<number, string> = {
    1: 'lg:grid-cols-1',
    2: 'lg:grid-cols-2',
    3: 'lg:grid-cols-3',
    4: 'lg:grid-cols-4',
};

/**
 * The headline numbers above a report table. On phones they sit two to a row, and an odd last tile takes the
 * whole row.
 */
export default function ReportTiles({ tiles, currency }: Props) {
    return (
        <div
            className={cn(
                'grid grid-cols-2 gap-3 [&>*:last-child:nth-child(odd)]:col-span-2 lg:[&>*:last-child:nth-child(odd)]:col-span-1',
                wideColumns[tiles.length] ?? 'lg:grid-cols-4',
            )}
        >
            {tiles.map((tile) => (
                <Card key={tile.label} size="sm">
                    <CardHeader>
                        <CardTitle className="text-xs font-medium text-muted-foreground">
                            {tile.label}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-1">
                        <p className="text-xl font-semibold break-words tabular-nums sm:text-2xl">
                            {formatReportValue(
                                tile.value,
                                tile.format,
                                currency,
                            )}
                        </p>
                        {tile.hint && (
                            <p className="text-xs text-muted-foreground">
                                {tile.hint}
                            </p>
                        )}
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
