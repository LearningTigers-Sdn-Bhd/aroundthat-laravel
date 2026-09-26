import { ChevronRight, History } from 'lucide-react';

/**
 * Today's count, the last row of the counter. Opens the list of today's redemptions.
 */
export default function ActivityBar({
    count,
    onOpen,
}: {
    count: number;
    onOpen: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onOpen}
            className="flex w-full items-center justify-between rounded-[1.75rem] border bg-card px-5 py-4 text-left shadow-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            <span className="flex items-center gap-3">
                <span className="grid size-10 place-items-center rounded-full bg-primary/10 text-primary">
                    <History className="size-5" />
                </span>
                <span>
                    <span className="block text-xs font-medium tracking-[0.18em] text-muted-foreground uppercase">
                        Today
                    </span>
                    <span className="text-lg font-semibold tabular-nums">
                        {count} {count === 1 ? 'redemption' : 'redemptions'}
                    </span>
                </span>
            </span>
            <span className="flex items-center gap-1 text-sm text-muted-foreground">
                View activity <ChevronRight className="size-4" />
            </span>
        </button>
    );
}
