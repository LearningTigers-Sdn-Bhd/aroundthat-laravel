import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronRight, ScanLine } from 'lucide-react';
import AttentionList from '@/components/app/dashboard/attention-list';
import QuickActions from '@/components/app/dashboard/quick-actions';
import type { QuickAction } from '@/components/app/dashboard/quick-actions';
import RecentRedemptions from '@/components/app/dashboard/recent-redemptions';
import RunningOffers from '@/components/app/dashboard/running-offers';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatReportValue } from '@/lib/reports';
import { dashboard } from '@/routes';
import { show as report } from '@/routes/reports';

type Props = {
    /** This month's headline numbers, or null for members who cannot see reports. */
    month: {
        label: string;
        currency: string;
        tiles: App.Data.DashboardTileData[];
    } | null;
    /** Vouchers used today at the member's outlets, for members who see today's activity but not reports. */
    today: { used: number } | null;
    quickActions: QuickAction[];
    attention: App.Data.DashboardAttentionData[];
    /** Deferred: undefined while loading, null when the member cannot see it. */
    recentRedemptions?: App.Data.DashboardRedemptionData[] | null;
    /** Deferred: undefined while loading, null when the member cannot see it. */
    offers?: App.Data.DashboardOfferData[] | null;
};

export default function Dashboard({
    month,
    today,
    quickActions,
    attention,
    recentRedemptions,
    offers,
}: Props) {
    const { workspace } = usePage().props;
    const showsLists = recentRedemptions !== null || offers !== null;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                {month ? (
                    <section className="flex flex-col gap-4">
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
                    </section>
                ) : (
                    <Heading
                        title={`Welcome to ${workspace?.business_name ?? 'your business'}`}
                        description={
                            quickActions.includes('counter')
                                ? 'Open the counter to check and redeem guest vouchers.'
                                : 'Use the menu to get started.'
                        }
                    />
                )}

                {today && (
                    <Card size="sm" className="max-w-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                <ScanLine className="size-4" />
                                Today at your outlets
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold tabular-nums">
                                {today.used}{' '}
                                <span className="text-sm font-normal text-muted-foreground">
                                    {today.used === 1
                                        ? 'voucher used'
                                        : 'vouchers used'}
                                </span>
                            </p>
                        </CardContent>
                    </Card>
                )}

                {quickActions.length > 0 && (
                    <QuickActions actions={quickActions} />
                )}

                <AttentionList items={attention} />

                {showsLists && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {recentRedemptions !== null && (
                            <RecentRedemptions
                                redemptions={recentRedemptions}
                            />
                        )}
                        {offers !== null && <RunningOffers items={offers} />}
                    </div>
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
