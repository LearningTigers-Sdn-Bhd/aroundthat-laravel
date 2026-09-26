import { Link } from '@inertiajs/react';
import { Ticket } from 'lucide-react';
import StatusBadge from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
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
import { formatDate } from '@/lib/format';
import { edit as editOffer, index as offers } from '@/routes/offers';
import { DashboardCardSkeleton } from './dashboard-card-skeleton';

/**
 * The business's offers that have not ended, the soonest to end first, with vouchers issued against the limit.
 */
export default function RunningOffers({
    items,
}: {
    items: App.Data.DashboardOfferData[] | undefined;
}) {
    if (items === undefined) {
        return <DashboardCardSkeleton />;
    }

    return (
        <Card size="sm">
            <CardHeader>
                <CardTitle>Your offers</CardTitle>
                <CardDescription>
                    Running and scheduled, ending soonest first.
                </CardDescription>
                <CardAction>
                    <Link
                        href={offers()}
                        prefetch
                        className="text-xs font-medium text-primary hover:underline"
                    >
                        See all
                    </Link>
                </CardAction>
            </CardHeader>
            <CardContent className={items.length > 0 ? 'px-0' : undefined}>
                {items.length === 0 ? (
                    <Empty className="py-6">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Ticket />
                            </EmptyMedia>
                            <EmptyTitle>No offers running</EmptyTitle>
                            <EmptyDescription>
                                Activate an offer so guests can claim vouchers.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <ul className="divide-y">
                        {items.map((offer) => (
                            <li key={offer.id}>
                                <Link
                                    href={editOffer(offer.id)}
                                    prefetch
                                    className="flex items-center gap-3 px-(--card-spacing) py-2.5 transition-colors hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">
                                            {offer.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground tabular-nums">
                                            {offer.voucher_limit === null
                                                ? `${offer.issued_count} vouchers issued`
                                                : `${offer.issued_count} of ${offer.voucher_limit} vouchers issued`}
                                            {' · '}
                                            Ends {formatDate(offer.ends_at)}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-1.5">
                                        {offer.ends_soon && (
                                            <Badge variant="outline">
                                                Ends soon
                                            </Badge>
                                        )}
                                        <StatusBadge status={offer.state} />
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
