import type { Amounts, EligibleCheck } from '@/components/app/counter/types';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney } from '@/lib/offers';

/**
 * The priced bill, turned toward the guest before the cashier accepts the voucher.
 */
export default function ConfirmStep({
    offer,
    amounts,
    busy,
    online,
    onAccept,
    onBack,
}: {
    offer: EligibleCheck['offer'];
    amounts: Amounts;
    busy: boolean;
    online: boolean;
    onAccept: () => void;
    onBack: () => void;
}) {
    return (
        <section className="mx-auto flex w-full max-w-lg flex-1 flex-col justify-center py-6">
            <h2 className="text-center text-sm font-medium tracking-[0.2em] text-primary uppercase">
                Show the guest
            </h2>
            {/* Paper, not chrome: the guest reads this card, so it stays light whatever the theme. */}
            <div className="mt-5 rounded-[2rem] border border-slate-200 bg-white p-6 text-slate-900 shadow-xl sm:p-8">
                <p className="text-sm font-medium text-slate-500">
                    {offer.name}
                </p>
                <div className="mt-6 space-y-4 text-lg tabular-nums">
                    <div className="flex justify-between gap-6">
                        <span className="text-slate-500">Full bill</span>
                        <strong>
                            {formatMoney(amounts.bill_amount, offer.currency)}
                        </strong>
                    </div>
                    <div className="flex justify-between gap-6 text-emerald-700">
                        <span>Discount</span>
                        <strong>
                            −{' '}
                            {formatMoney(
                                amounts.discount_amount,
                                offer.currency,
                            )}
                        </strong>
                    </div>
                    <div className="h-px bg-slate-200" />
                    <div className="flex items-end justify-between gap-6">
                        <span className="pb-1 text-slate-500">Guest pays</span>
                        <strong className="text-3xl tracking-tight sm:text-4xl">
                            {formatMoney(amounts.net_amount, offer.currency)}
                        </strong>
                    </div>
                </div>
                {amounts.capped && (
                    <p className="mt-6 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        The maximum discount for this voucher has been applied.
                    </p>
                )}
                {offer.discount_type === 'free_item' && (
                    <p className="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        Give the guest: {offer.free_item}
                    </p>
                )}
            </div>
            {!online && (
                <p
                    role="alert"
                    className="mt-4 text-center text-sm text-destructive"
                >
                    Connect to the internet before accepting this voucher.
                </p>
            )}
            <Button
                size="lg"
                onClick={onAccept}
                disabled={busy || !online}
                className="mt-6 h-16 w-full text-lg font-bold tracking-[0.08em] uppercase"
            >
                {busy ? <Spinner /> : 'Accept voucher'}
            </Button>
            <Button
                variant="ghost"
                size="lg"
                onClick={onBack}
                disabled={busy}
                className="mt-2 w-full text-muted-foreground"
            >
                Change the bill
            </Button>
        </section>
    );
}
