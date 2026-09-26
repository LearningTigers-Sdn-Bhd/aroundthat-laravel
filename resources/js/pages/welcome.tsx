import { Head } from '@inertiajs/react';
import { useEffect } from 'react';

import { EarlyAccessOffer } from '@/components/landing/early-access-offer';
import { Faq } from '@/components/landing/faq';
import { Features } from '@/components/landing/features';
import { FinalCta } from '@/components/landing/final-cta';
import { Footer } from '@/components/landing/footer';
import { Hero } from '@/components/landing/hero';
import { HowItWorks } from '@/components/landing/how-it-works';
import { Navbar } from '@/components/landing/navbar';
import { PainTransformation } from '@/components/landing/pain-transformation';
import { appTagline } from '@/lib/brand';

export default function Welcome() {
    useEffect(() => {
        const root = document.documentElement;
        root.classList.add('landing');

        return () => {
            root.classList.remove('landing');
        };
    }, []);

    return (
        <>
            <Head title={appTagline} />

            <div className="landing min-h-screen scroll-smooth bg-background text-foreground">
                <Navbar />
                <main>
                    <Hero />
                    <PainTransformation />
                    <HowItWorks />
                    <Features />
                    <EarlyAccessOffer />
                    <Faq />
                    <FinalCta />
                </main>
                <Footer />
            </div>
        </>
    );
}
