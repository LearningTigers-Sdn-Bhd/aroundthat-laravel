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
