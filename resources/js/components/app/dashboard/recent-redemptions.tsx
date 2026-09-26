import { Link } from '@inertiajs/react';
import { TicketCheck } from 'lucide-react';
import StatusBadge from '@/components/status-badge';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { formatDateTime, formatRelative } from '@/lib/format';
import { formatMoney } from '@/lib/offers';
import { show as report } from '@/routes/reports';
import { DashboardCardSkeleton } from './dashboard-card-skeleton';

/**
 * The latest vouchers used at the member's outlets, cancelled ones marked.
 */
export default function RecentRedemptions({
    redemptions,
}: {
    redemptions: App.Data.DashboardRedemptionData[] | undefined;
}) {
    if (redemptions === undefined) {
        return <DashboardCardSkeleton />;
    }

    return (
        <Card size="sm">
            <CardHeader>
                <CardTitle>Latest vouchers used</CardTitle>
                <CardDescription>
                    At your outlets and for your offers.
                </CardDescription>
                <CardAction>
                    <Link
                        href={report('redemptions')}
                        prefetch
                        className="text-xs font-medium text-primary hover:underline"
                    >
                        See all
                    </Link>
                </CardAction>
            </CardHeader>
            <CardContent
                className={redemptions.length > 0 ? 'px-0' : undefined}
            >
                {redemptions.length === 0 ? (
                    <Empty className="py-6">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <TicketCheck />
                            </EmptyMedia>
                            <EmptyTitle>No vouchers used yet</EmptyTitle>
                            <EmptyDescription>
                                They show here as soon as the counter redeems
                                one.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <ul className="divide-y">
                        {redemptions.map((redemption) => (
                            <li
                                key={redemption.id}
                                className="flex items-center gap-3 px-(--card-spacing) py-2.5"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-medium">
                                        {redemption.offer_name}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {redemption.outlet_name} ·{' '}
                                        <time
                                            dateTime={redemption.redeemed_at}
                                            title={formatDateTime(
                                                redemption.redeemed_at,
                                            )}
                                        >
                                            {formatRelative(
                                                redemption.redeemed_at,
                                            )}
                                        </time>
                                    </p>
                                </div>
                                {redemption.is_cancelled ? (
                                    <StatusBadge status="cancelled" />
                                ) : (
                                    <div className="text-right tabular-nums">
                                        <p className="font-medium">
                                            {formatMoney(
                                                redemption.bill_amount,
                                                redemption.currency,
                                            )}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            −
                                            {formatMoney(
                                                redemption.discount_amount,
                                                redemption.currency,
                                            )}
                                        </p>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
