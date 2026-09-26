import { Check, ScanLine } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/offers';

/**
 * The redemption the cashier just accepted. It returns to the camera on its own unless the cashier touches it.
 */
export default function DoneStep({
    redemption,
    held,
    onNext,
    onHold,
}: {
    redemption: App.Data.RedemptionData;
    held: boolean;
    onNext: () => void;
    onHold: () => void;
}) {
    return (
        <section
            role="status"
            onPointerDown={onHold}
            onKeyDown={onHold}
            className="flex flex-1 flex-col items-center justify-center py-10 text-center"
        >
            <div className="grid size-24 place-items-center rounded-full bg-primary text-primary-foreground shadow-[0_0_60px] shadow-primary/20">
                <Check className="size-12 stroke-[3]" />
            </div>
            <h2 className="mt-8 text-sm font-medium tracking-[0.2em] text-primary uppercase">
                Discount given
            </h2>
            <p className="mt-2 text-5xl font-semibold tracking-tight tabular-nums sm:text-7xl">
                {formatMoney(redemption.discount_amount, redemption.currency)}
            </p>
            <p className="mt-5 text-lg text-muted-foreground">
                Guest pays{' '}
                <strong className="text-foreground">
                    {formatMoney(redemption.net_amount, redemption.currency)}
                </strong>
            </p>
            <p className="mt-1 text-sm text-muted-foreground">
                {redemption.offer_name}
            </p>
            <Button
                size="lg"
                onClick={onNext}
                className="mt-10 h-14 min-w-64 text-base"
            >
                <ScanLine data-icon="inline-start" /> Next guest
            </Button>
            {!held && (
                <p className="mt-4 text-xs text-muted-foreground">
                    The camera comes back on its own in 10 seconds
                </p>
            )}
        </section>
    );
}
