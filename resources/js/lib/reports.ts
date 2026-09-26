const countFormat = new Intl.NumberFormat(undefined);

const moneyFormat = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const percentFormat = new Intl.NumberFormat(undefined, {
    maximumFractionDigits: 1,
});

/** One value in a report row: a name, a count, a money amount as a decimal string, a percent, or nothing. */
export type ReportValue = string | number | boolean | null | undefined;

/** A report value as the page shows it: "1,204", "MYR 1,250.50", "12.5%", or "—" when there is none. */
export function formatReportValue(
    value: ReportValue,
    format: App.Enums.ReportValueFormat,
    currency: string,
): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    switch (format) {
        case 'count':
            return countFormat.format(Number(value));
        case 'money':
            return `${currency} ${moneyFormat.format(Number(value))}`;
        case 'percent':
            return `${percentFormat.format(Number(value))}%`;
        default:
            return String(value);
    }
}
