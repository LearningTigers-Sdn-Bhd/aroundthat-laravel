import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatReportValue } from '@/lib/reports';

type Props = {
    tiles: App.Support.Reports.ReportTile[];
    currency: string;
};

/** The headline numbers above a report table. */
export default function ReportTiles({ tiles, currency }: Props) {
    return (
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            {tiles.map((tile) => (
                <Card key={tile.label} size="sm">
                    <CardHeader>
                        <CardTitle className="text-xs font-medium text-muted-foreground">
                            {tile.label}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-1">
                        <p className="text-xl font-semibold tabular-nums sm:text-2xl">
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
