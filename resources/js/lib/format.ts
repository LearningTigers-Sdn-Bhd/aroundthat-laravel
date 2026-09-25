const dateTimeFormat = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

const dateFormat = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' });

/** An ISO date-time from the server as "24 Sept 2026, 09:15", or "—" when missing. */
export function formatDateTime(value: string | null | undefined): string {
    return value ? dateTimeFormat.format(new Date(value)) : '—';
}

/** An ISO date-time from the server as "24 Sept 2026", or "—" when missing. */
export function formatDate(value: string | null | undefined): string {
    return value ? dateFormat.format(new Date(value)) : '—';
}

/** "contact_email" → "Contact email". */
export function humanize(value: string): string {
    const words = value.replaceAll('_', ' ');

    return words.charAt(0).toUpperCase() + words.slice(1);
}

const relativeFormat = new Intl.RelativeTimeFormat(undefined, {
    numeric: 'auto',
});

const relativeSteps: [Intl.RelativeTimeFormatUnit, number][] = [
    ['second', 60],
    ['minute', 60],
    ['hour', 24],
    ['day', 7],
    ['week', 4.35],
    ['month', 12],
    ['year', Infinity],
];

/** An ISO date-time from the server as "5 minutes ago" or "in 3 days". */
export function formatRelative(value: string): string {
    let amount = (new Date(value).getTime() - Date.now()) / 1000;

    for (const [unit, size] of relativeSteps) {
        if (Math.abs(amount) < size) {
            return relativeFormat.format(Math.round(amount), unit);
        }

        amount /= size;
    }

    return formatDate(value);
}
