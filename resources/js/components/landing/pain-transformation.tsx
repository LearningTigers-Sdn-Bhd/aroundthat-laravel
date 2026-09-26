import storefrontImage from '@/assets/landing/storefront-owner.webp';

const benefits = [
    {
        title: 'Be recommended',
        body: 'Hotels and travel partners show your business on their concierge page.',
    },
    {
        title: 'Offer a reason to visit',
        body: 'Publish trackable offers without printing or distributing paper vouchers.',
    },
    {
        title: 'Know what worked',
        body: 'See views, claims, and redemptions for every outlet without seeing guest data.',
    },
];

export function PainTransformation() {
    return (
        <section className="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:py-28">
            <div className="grid items-center gap-12 lg:grid-cols-[0.92fr_1.08fr] lg:gap-20">
                <div className="relative aspect-[4/5] overflow-hidden sm:aspect-[5/4] lg:aspect-[4/5]">
                    <img
                        src={storefrontImage}
                        alt="A café owner preparing a table outside her Malaysian shop-house storefront"
                        width={1122}
                        height={1402}
                        loading="lazy"
                        className="absolute inset-0 size-full object-cover"
                    />
                </div>

                <div>
                    <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                        Good local places should not be invisible
                    </p>
                    <h2 className="mt-4 font-heading text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                        Become part of the answer guests already ask for.
                    </h2>
                    <p className="mt-5 max-w-xl text-lg leading-relaxed text-muted-foreground">
                        Travellers ask the front desk where to eat, shop, and
                        explore. AroundThat helps hotels recommend your
                        business—and shows whether that recommendation brought
                        somebody through your door.
                    </p>

                    <ul className="mt-9 border-t">
                        {benefits.map((benefit) => (
                            <li
                                key={benefit.title}
                                className="grid gap-2 border-b py-5 sm:grid-cols-[11rem_1fr] sm:gap-6"
                            >
                                <h3 className="font-heading font-semibold">
                                    {benefit.title}
                                </h3>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    {benefit.body}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </section>
    );
}
