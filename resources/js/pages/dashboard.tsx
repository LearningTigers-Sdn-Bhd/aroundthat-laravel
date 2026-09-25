import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronRight, LayoutGrid, ScanLine } from 'lucide-react';
import ButtonLink from '@/components/button-link';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { formatReportValue } from '@/lib/reports';
import { dashboard } from '@/routes';
import { show as counter } from '@/routes/counter';
import { show as report } from '@/routes/reports';

type Props = {
    /** This month's headline numbers, or null for members who cannot see reports. */
    month: {
        label: string;
        currency: string;
        tiles: App.Data.DashboardTileData[];
    } | null;
};

export default function Dashboard({ month }: Props) {
    const { workspace } = usePage().props;
    const canScan = workspace?.abilities.includes('scan') ?? false;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                {month ? (
                    <>
                        <Heading
                            title="This month"
                            description={`${month.label}. Tap a number to open its report.`}
                        />

                        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            {month.tiles.map((tile) => (
                                <Link
                                    key={tile.label}
                                    href={report(tile.report)}
                                    prefetch
                                    className="rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <Card
                                        size="sm"
                                        className="h-full transition-colors hover:bg-muted/50"
                                    >
                                        <CardHeader>
                                            <CardTitle className="flex items-center justify-between gap-2 text-xs font-medium text-muted-foreground">
                                                {tile.label}
                                                <ChevronRight className="size-4" />
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="flex flex-col gap-1">
                                            <p className="text-xl font-semibold break-words tabular-nums sm:text-2xl">
                                                {formatReportValue(
                                                    tile.value,
                                                    tile.format,
                                                    month.currency,
                                                )}
                                            </p>
                                            {tile.hint && (
                                                <p className="text-xs text-muted-foreground">
                                                    {tile.hint}
                                                </p>
                                            )}
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    </>
                ) : (
                    <Empty className="border">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <LayoutGrid />
                            </EmptyMedia>
                            <EmptyTitle>
                                Welcome to {workspace?.business_name}
                            </EmptyTitle>
                            <EmptyDescription>
                                {canScan
                                    ? 'Open the counter to check and redeem guest vouchers.'
                                    : 'Use the menu to get started.'}
                            </EmptyDescription>
                        </EmptyHeader>
                        {canScan && (
                            <EmptyContent>
                                <ButtonLink href={counter()}>
                                    <ScanLine data-icon="inline-start" />
                                    Open counter
                                </ButtonLink>
                            </EmptyContent>
                        )}
                    </Empty>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
