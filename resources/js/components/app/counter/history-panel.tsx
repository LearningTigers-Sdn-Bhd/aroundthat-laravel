import { Clock3, History } from 'lucide-react';
import ReasonDialog from '@/components/reason-dialog';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { formatMoney } from '@/lib/offers';
import { cancel } from '@/routes/counter';

function timeOf(value: string): string {
    return new Date(value).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Today's redemptions at the outlet, in a sheet from the bottom, with a way to cancel the ones still in the window.
 */
export default function HistoryPanel({
    open,
    today,
    outletName,
    onClose,
}: {
    open: boolean;
    today: { redemptions: App.Data.RedemptionData[]; total: number };
    outletName: string;
    onClose: () => void;
}) {
    return (
        <Sheet open={open} onOpenChange={(next) => !next && onClose()}>
            {/* Matches the side variant's own key so tailwind-merge drops its h-auto: the list keeps one height
                whether it has one row or ten. */}
            <SheetContent
                side="bottom"
                className="rounded-t-[2rem] pb-2 data-[side=bottom]:h-[85dvh]"
            >
                <SheetHeader className="shrink-0">
                    <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                        {outletName}
                    </p>
                    <SheetTitle className="text-2xl">
                        Today's redemptions
                    </SheetTitle>
                    <SheetDescription>
                        {today.total > 10
                            ? `The latest 10 of ${today.total}`
                            : `${today.total} today`}
                    </SheetDescription>
                </SheetHeader>

                <div className="min-h-0 flex-1 overflow-y-auto px-4 pb-4 sm:px-6">
                    {today.redemptions.length === 0 ? (
                        <div className="grid min-h-56 place-items-center text-center text-muted-foreground">
                            <div>
                                <History className="mx-auto size-8 opacity-50" />
                                <p className="mt-3">
                                    No vouchers redeemed today.
                                </p>
                            </div>
                        </div>
                    ) : (
                        <ul className="space-y-2">
                            {today.redemptions.map((redemption) => (
                                <li
                                    key={redemption.id}
                                    className={`rounded-2xl border bg-muted p-4 ${redemption.cancelled_at ? 'opacity-65' : ''}`}
                                >
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="min-w-0">
                                            <p className="truncate font-semibold">
                                                {redemption.offer_name}
                                            </p>
                                            <p className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                                                <Clock3 className="size-3.5" />
                                                {timeOf(redemption.redeemed_at)}
                                                <span className="font-mono">
                                                    · {redemption.code_prefix}…
                                                </span>
                                                {redemption.cashier_name &&
                                                    ` · ${redemption.cashier_name}`}
                                            </p>
                                        </div>
                                        <p className="shrink-0 text-lg font-semibold text-primary tabular-nums">
                                            −{' '}
                                            {formatMoney(
                                                redemption.discount_amount,
                                                redemption.currency,
                                            )}
                                        </p>
                                    </div>
                                    {redemption.cancelled_at ? (
                                        <p className="mt-3 rounded-xl bg-background/40 px-3 py-2 text-xs text-muted-foreground">
                                            Cancelled
                                            {redemption.cancelled_by_name &&
                                                ` by ${redemption.cancelled_by_name}`}{' '}
                                            at {timeOf(redemption.cancelled_at)}
                                        </p>
                                    ) : redemption.can_cancel ? (
                                        <ReasonDialog
                                            trigger={
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="mt-3"
                                                >
                                                    Cancel this redemption
                                                </Button>
                                            }
                                            title="Cancel this redemption?"
                                            description="The voucher gets the use back. The redemption stays on the list, marked cancelled."
                                            form={cancel.form(redemption.id)}
                                            submitLabel="Cancel redemption"
                                            destructive
                                        />
                                    ) : (
                                        <p className="mt-3 text-xs text-muted-foreground">
                                            Too late to cancel here · ask an
                                            administrator
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
