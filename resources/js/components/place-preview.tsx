import { Globe, Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/format';

const weekdays = [
    ['1', 'Monday'],
    ['2', 'Tuesday'],
    ['3', 'Wednesday'],
    ['4', 'Thursday'],
    ['5', 'Friday'],
    ['6', 'Saturday'],
    ['7', 'Sunday'],
] as const;

function formatPeriods(periods: App.Data.OpeningPeriodData[]): string {
    if (periods.length === 0) {
        return 'Closed';
    }

    if (
        periods.length === 1 &&
        periods[0].opens === '00:00' &&
        periods[0].closes === '00:00'
    ) {
        return 'Open 24 hours';
    }

    return periods
        .map((period) => `${period.opens}–${period.closes}`)
        .join(', ');
}

/**
 * An outlet's public page as a visitor would see it, built only from what is saved.
 */
export default function PlacePreview({
    preview,
}: {
    preview: App.Data.PlacePreviewData;
}) {
    const { place, hours, business } = preview;
    const cover = preview.images.find((image) => image.kind === 'cover');
    const gallery = preview.images.filter((image) => image.kind === 'gallery');
    const hasHours = Object.values(hours.regular_hours).some(
        (periods) => periods.length > 0,
    );

    return (
        <article className="overflow-hidden rounded-lg border bg-card">
            {cover ? (
                <img
                    src={cover.url}
                    alt={cover.alt_text}
                    className="aspect-[21/9] w-full object-cover"
                />
            ) : (
                <div className="flex aspect-[21/9] w-full items-center justify-center bg-muted text-sm text-muted-foreground">
                    No cover photo
                </div>
            )}

            <div className="space-y-6 p-6">
                <header className="space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        {place.category && (
                            <Badge variant="secondary">
                                {place.category.name}
                            </Badge>
                        )}
                        {hasHours && (
                            <Badge variant="outline">
                                {preview.is_open_now
                                    ? `Open now · closes ${preview.closes_at}`
                                    : preview.next_opens_at
                                      ? `Closed · opens ${preview.next_opens_at}`
                                      : 'Closed now'}
                            </Badge>
                        )}
                    </div>
                    <h2 className="text-2xl font-semibold tracking-tight">
                        {preview.name}
                    </h2>
                    <p className="text-muted-foreground">
                        {place.summary ?? 'No summary yet.'}
                    </p>
                    {place.tags.length > 0 && (
                        <ul className="flex flex-wrap gap-1.5">
                            {place.tags.map((tag) => (
                                <li key={tag.id}>
                                    <Badge variant="outline">{tag.name}</Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </header>

                {place.description && (
                    <p className="text-sm whitespace-pre-line">
                        {place.description}
                    </p>
                )}

                <div className="grid gap-6 sm:grid-cols-2">
                    <section className="space-y-2 text-sm">
                        <h3 className="font-medium">Visit</h3>
                        <p className="flex gap-2">
                            <MapPin className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                            {preview.address}
                        </p>
                        {preview.contact_phone && (
                            <p className="flex gap-2">
                                <Phone className="size-4 text-muted-foreground" />
                                {preview.contact_phone}
                            </p>
                        )}
                        {place.whatsapp && (
                            <p className="flex gap-2">
                                <MessageCircle className="size-4 text-muted-foreground" />
                                WhatsApp {place.whatsapp}
                            </p>
                        )}
                        {preview.contact_email && (
                            <p className="flex gap-2">
                                <Mail className="size-4 text-muted-foreground" />
                                {preview.contact_email}
                            </p>
                        )}
                        {[place.website, place.facebook, place.instagram]
                            .filter((link): link is string => !!link)
                            .map((link) => (
                                <p key={link} className="flex gap-2">
                                    <Globe className="size-4 shrink-0 text-muted-foreground" />
                                    <a
                                        href={link}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="truncate underline underline-offset-4"
                                    >
                                        {link.replace(/^https?:\/\//, '')}
                                    </a>
                                </p>
                            ))}
                    </section>

                    <section className="space-y-2 text-sm">
                        <h3 className="font-medium">Opening hours</h3>
                        {hasHours ? (
                            <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                                {weekdays.map(([day, name]) => (
                                    <div key={day} className="contents">
                                        <dt className="text-muted-foreground">
                                            {name}
                                        </dt>
                                        <dd>
                                            {formatPeriods(
                                                hours.regular_hours[day] ?? [],
                                            )}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        ) : (
                            <p className="text-muted-foreground">
                                No hours yet.
                            </p>
                        )}
                        {hours.date_exceptions.length > 0 && (
                            <ul className="space-y-1 pt-2">
                                {hours.date_exceptions.map((exception) => (
                                    <li key={exception.date}>
                                        <span className="text-muted-foreground">
                                            {formatDate(exception.date)}
                                            {exception.note &&
                                                ` (${exception.note})`}
                                            :{' '}
                                        </span>
                                        {exception.is_closed
                                            ? 'Closed'
                                            : formatPeriods(exception.periods)}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                {gallery.length > 0 && (
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        {gallery.map((image) => (
                            <img
                                key={image.id}
                                src={image.url}
                                alt={image.alt_text}
                                loading="lazy"
                                className="aspect-[4/3] w-full rounded-md object-cover"
                            />
                        ))}
                    </div>
                )}

                <footer className="flex items-center gap-3 border-t pt-4">
                    {business.logo && (
                        <img
                            src={business.logo.url}
                            alt={business.logo.alt_text}
                            className="size-10 rounded-md border object-contain"
                        />
                    )}
                    <div className="text-sm">
                        <p className="font-medium">{preview.business_name}</p>
                        {business.summary && (
                            <p className="text-muted-foreground">
                                {business.summary}
                            </p>
                        )}
                    </div>
                </footer>
            </div>
        </article>
    );
}
