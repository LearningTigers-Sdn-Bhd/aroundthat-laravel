import type { ComboboxOption } from '@/components/combobox-field';

/**
 * Timezones as options, shown with spaces instead of underscores.
 */
export function timezoneOptions(timezones: string[]): ComboboxOption[] {
    return timezones.map((timezone) => ({
        value: timezone,
        label: timezone.replaceAll('_', ' '),
    }));
}

/**
 * Country codes as options, named in English and sorted by name.
 */
export function countryOptions(countryCodes: string[]): ComboboxOption[] {
    const names = new Intl.DisplayNames(['en'], { type: 'region' });

    return countryCodes
        .map((code) => ({ value: code, label: names.of(code) ?? code }))
        .sort((a, b) => a.label.localeCompare(b.label));
}
