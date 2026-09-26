import ctaImage from '@/assets/landing/twilight-storefront.webp';
import { accessRequestEmail } from '@/lib/brand';

import { RequestAccessLink } from './request-access-link';

export function FinalCta() {
    return (
        <section className="relative isolate flex min-h-[34rem] items-center overflow-hidden text-landing-inverse-foreground sm:min-h-[38rem]">
            <img
                src={ctaImage}
                alt="A welcoming Malaysian neighbourhood restaurant at twilight"
                width={1986}
                height={792}
                loading="lazy"
                className="absolute inset-0 -z-20 size-full object-cover object-[62%_center] sm:object-center"
            />
            <div
                aria-hidden="true"
                className="absolute inset-0 -z-10 [background-image:var(--landing-cta-overlay)]"
            />
            <div className="mx-auto w-full max-w-6xl px-4 py-20 sm:px-6">
                <div className="mx-auto max-w-2xl text-center [text-shadow:var(--landing-photo-text-shadow)]">
                    <p className="text-xs font-semibold tracking-[0.18em] text-landing-inverse-muted uppercase">
                        Join early access
                    </p>
                    <h2 className="mt-4 font-heading text-3xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl">
                        Be the place guests hear about first.
                    </h2>
                    <p className="mx-auto mt-5 max-w-lg text-lg leading-relaxed text-landing-inverse-muted">
                        Tell us about your business and we will help you get set
                        up.
                    </p>
                    <div className="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <RequestAccessLink className="px-7 py-3 text-base" />
                        <p className="text-sm text-landing-inverse-muted">
                            or email {accessRequestEmail}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}
