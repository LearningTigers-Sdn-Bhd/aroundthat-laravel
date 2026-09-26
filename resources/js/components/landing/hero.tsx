import { MapPin } from 'lucide-react';

import heroImage from '@/assets/landing/hero-discovery.webp';
import { appTagline } from '@/lib/brand';

import { RequestAccessLink } from './request-access-link';

export function Hero() {
    return (
        <section
            id="top"
            className="relative isolate flex min-h-[42rem] items-center overflow-hidden text-landing-inverse-foreground sm:min-h-[44rem]"
        >
            <img
                src={heroImage}
                alt="A traveller discovering a neighbourhood café on a Malaysian waterfront street"
                width={1536}
                height={1024}
                fetchPriority="high"
                className="absolute inset-0 -z-20 size-full object-cover object-[66%_center] sm:object-[62%_center] lg:object-center"
            />
            <div
                aria-hidden="true"
                className="absolute inset-0 -z-10 [background-image:var(--landing-hero-overlay)]"
            />

            <div className="relative mx-auto flex min-h-[42rem] w-full max-w-6xl items-center px-4 py-16 pb-48 sm:min-h-[44rem] sm:px-6 sm:py-20 sm:pb-44 lg:py-24 lg:pb-24">
                <div className="max-w-xl [text-shadow:var(--landing-photo-text-shadow)]">
                    <p className="mb-5 text-xs font-semibold tracking-[0.18em] text-landing-inverse-muted uppercase">
                        Get found by guests nearby
                    </p>
                    <h1 className="max-w-xl font-heading text-4xl leading-[1.06] font-semibold tracking-[-0.035em] text-balance sm:text-5xl lg:text-6xl">
                        {appTagline}
                    </h1>
                    <p className="mt-6 max-w-lg text-lg leading-relaxed text-landing-inverse-muted">
                        Hotels and travel partners recommend nearby places to
                        their guests. AroundThat puts your business and your
                        voucher offers in that list, and shows which
                        recommendations led to visits.
                    </p>
                    <div className="mt-8 flex flex-wrap items-center gap-4">
                        <RequestAccessLink className="px-6 py-3 text-base" />
                        <a
                            href="#how-it-works"
                            className="rounded-md border border-landing-inverse-foreground/30 px-6 py-3 text-base font-medium text-landing-inverse-foreground transition-colors hover:bg-landing-inverse-foreground/10 focus-visible:ring-3 focus-visible:ring-ring/60 focus-visible:outline-none"
                        >
                            See how it works
                        </a>
                    </div>
                </div>

                <div className="absolute right-4 bottom-6 left-4 border border-border bg-card/95 p-4 text-card-foreground shadow-sm sm:right-6 sm:bottom-8 sm:left-auto sm:w-72">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-xs font-medium tracking-wide text-primary uppercase">
                                Recommended nearby
                            </p>
                            <p className="mt-1 font-heading text-lg font-semibold">
                                Waterfront café
                            </p>
                        </div>
                        <span className="flex items-center gap-1 text-xs whitespace-nowrap text-muted-foreground">
                            <MapPin className="size-3.5" aria-hidden />
                            350 m
                        </span>
                    </div>
                    <p className="mt-3 border-t pt-3 text-sm">
                        Free drink with any meal
                    </p>
                </div>
            </div>
        </section>
    );
}
