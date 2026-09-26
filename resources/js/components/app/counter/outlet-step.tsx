import { ArrowLeft, ChevronRight, MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * The voucher works at more than one of the cashier's other outlets, so the cashier says where the guest is.
 */
export default function OutletStep({
    outlets,
    message,
    onChoose,
    onBack,
}: {
    outlets: App.Data.OutletOptionData[];
    message: string;
    onChoose: (outlet: App.Data.OutletOptionData) => void;
    onBack: () => void;
}) {
    return (
        <section className="mx-auto flex w-full max-w-lg flex-1 flex-col justify-center py-6">
            <Button
                variant="ghost"
                onClick={onBack}
                className="mb-6 w-fit text-muted-foreground"
            >
                <ArrowLeft data-icon="inline-start" /> Scan another voucher
            </Button>
            <p className="text-sm font-medium tracking-[0.2em] text-primary uppercase">
                Which outlet?
            </p>
            <h2 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                Where is the guest?
            </h2>
            <p className="mt-3 text-muted-foreground">{message}</p>
            <ul className="mt-8 space-y-3">
                {outlets.map((outlet) => (
                    <li key={outlet.id}>
                        <button
                            type="button"
                            onClick={() => onChoose(outlet)}
                            className="flex w-full items-center gap-4 rounded-3xl border bg-card px-5 py-5 text-left transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <span className="grid size-10 shrink-0 place-items-center rounded-full bg-primary/10 text-primary">
                                <MapPin className="size-5" />
                            </span>
                            <span className="min-w-0 flex-1 truncate text-lg font-semibold">
                                {outlet.name}
                            </span>
                            <ChevronRight className="size-5 text-muted-foreground" />
                        </button>
                    </li>
                ))}
            </ul>
        </section>
    );
}
