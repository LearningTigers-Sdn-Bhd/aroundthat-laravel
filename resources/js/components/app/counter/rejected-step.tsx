import { CircleX, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * Why the voucher was refused, from the check or from the redemption itself.
 */
export default function RejectedStep({
    message,
    onRetry,
}: {
    message: string;
    onRetry: () => void;
}) {
    return (
        <section
            role="alert"
            className="flex flex-1 flex-col items-center justify-center py-10 text-center"
        >
            <div className="grid size-24 place-items-center rounded-full bg-destructive/10 text-destructive">
                <CircleX className="size-12" />
            </div>
            <p className="mt-8 text-sm font-medium tracking-[0.2em] text-destructive uppercase">
                Voucher not accepted
            </p>
            <h2 className="mt-3 max-w-md text-3xl font-semibold tracking-tight sm:text-4xl">
                {message}
            </h2>
            <Button
                size="lg"
                onClick={onRetry}
                className="mt-10 h-14 min-w-64 text-base"
            >
                <RotateCcw data-icon="inline-start" /> Try again
            </Button>
        </section>
    );
}
