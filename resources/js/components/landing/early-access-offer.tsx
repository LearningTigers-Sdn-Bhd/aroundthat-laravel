// The whole "free during early access" promise lives in this file.
// To remove it, delete this file and its <EarlyAccessOffer /> line in pages/home/index.tsx.
export function EarlyAccessOffer() {
    return (
        <section className="border-y bg-landing-tint">
            <div className="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-10 sm:px-6 md:flex-row md:items-center md:justify-between md:gap-10">
                <div className="max-w-3xl">
                    <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                        Early access
                    </p>
                    <h2 className="mt-2 font-heading text-2xl font-semibold">
                        Free while AroundThat is in early access.
                    </h2>
                </div>
                <p className="whitespace-nowrap text-muted-foreground md:text-right">
                    No card. No contract.
                </p>
            </div>
        </section>
    );
}
