import { Head, Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import PlacePreview from '@/components/place-preview';
import { home } from '@/routes';

/**
 * An outlet's public page, for visitors.
 */
export default function ShowPlace({
    place,
}: {
    place: App.Data.PlacePreviewData;
}) {
    return (
        <>
            <Head title={place.name}>
                {place.place.summary && (
                    <meta name="description" content={place.place.summary} />
                )}
            </Head>

            <div className="min-h-screen bg-background">
                <header className="mx-auto flex max-w-3xl items-center px-4 py-4">
                    <Link href={home()} className="flex items-center">
                        <AppLogo />
                    </Link>
                </header>

                <main className="mx-auto max-w-3xl px-4 pb-12">
                    <PlacePreview preview={place} />
                </main>
            </div>
        </>
    );
}
