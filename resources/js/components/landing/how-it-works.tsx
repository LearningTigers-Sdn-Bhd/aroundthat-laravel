const steps = [
    {
        title: 'Get onboarded',
        body: 'Send us a request. We set up your business account and your first owner login.',
    },
    {
        title: 'Add your outlets',
        body: 'Fill in your profile, opening hours, photos, and each outlet. We review it before it goes live.',
    },
    {
        title: 'Publish an offer',
        body: 'Create a voucher offer and choose the outlets that accept it.',
    },
    {
        title: 'Guests come in',
        body: 'Guests see you on a partner concierge page, show a code, and your staff scan it.',
    },
];

export function HowItWorks() {
    return (
        <section
            id="how-it-works"
            className="scroll-mt-20 border-y bg-muted py-20"
        >
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:py-4">
                <div className="max-w-2xl">
                    <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                        How it works
                    </p>
                    <h2 className="mt-2 font-heading text-3xl font-semibold tracking-tight sm:text-4xl">
                        From sign-up to your first guest in four steps
                    </h2>
                </div>

                <ol className="mt-12 border-t lg:grid lg:grid-cols-4">
                    {steps.map((step, index) => (
                        <li
                            key={step.title}
                            className="relative border-b py-7 pl-14 lg:border-r lg:border-b-0 lg:px-6 lg:py-8 lg:first:pl-0 lg:last:border-r-0"
                        >
                            <span className="absolute top-7 left-0 font-heading text-sm font-semibold text-primary lg:static lg:block">
                                {String(index + 1).padStart(2, '0')}
                            </span>
                            <h3 className="font-heading text-lg font-semibold lg:mt-8">
                                {step.title}
                            </h3>
                            <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                                {step.body}
                            </p>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
