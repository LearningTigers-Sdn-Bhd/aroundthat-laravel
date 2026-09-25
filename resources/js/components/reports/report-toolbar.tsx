import { router, usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { exportMethod as exportReport, show } from '@/routes/reports';

type Query = {
    period: string;
    from?: string;
    to?: string;
    group?: string;
    offer?: string;
    outlet?: string;
};

const ALL = '__all__';

/** How long custom dates settle before the report reloads, so typing a year does not load every step. */
const DATE_SETTLE_MS = 600;

/** The report's current choices as a query string, leaving out what is not set. */
function currentQuery(report: App.Data.Reports.ReportPageData): Query {
    return {
        period: report.period,
        ...(report.period === 'custom' && { from: report.from, to: report.to }),
        group: report.grouping,
        ...(report.offer && { offer: report.offer }),
        ...(report.outlet && { outlet: report.outlet }),
    };
}

/**
 * The shared controls of every report: the dates, what each row stands for, the report's filters and the CSV.
 * Each change reloads the report in place.
 */
export default function ReportToolbar({
    report,
}: {
    report: App.Data.Reports.ReportPageData;
}) {
    const { errors } = usePage().props;
    const [loading, setLoading] = useState(false);
    const [custom, setCustom] = useState(report.period === 'custom');
    const [dates, setDates] = useState({ from: report.from, to: report.to });
    const query = currentQuery(report);

    const visit = (next: Query) => {
        const cleaned = Object.fromEntries(
            Object.entries(next).filter(([, value]) => value),
        );

        router.get(show.url(report.key, { query: cleaned }), undefined, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    useEffect(() => {
        if (!custom || !dates.from || !dates.to) {
            return;
        }

        if (
            report.period === 'custom' &&
            dates.from === report.from &&
            dates.to === report.to
        ) {
            return;
        }

        const timer = setTimeout(
            () =>
                visit({
                    ...query,
                    period: 'custom',
                    from: dates.from,
                    to: dates.to,
                }),
            DATE_SETTLE_MS,
        );

        return () => clearTimeout(timer);
        // Only a change to the dates reloads the report here; the other choices reload on their own.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [custom, dates.from, dates.to]);

    const choosePeriod = (period: string | null) => {
        if (period === 'custom') {
            setDates({ from: report.from, to: report.to });
            setCustom(true);

            return;
        }

        if (period) {
            setCustom(false);
            visit({ ...query, period, from: undefined, to: undefined });
        }
    };

    const periodValue = custom ? 'custom' : report.period;

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-end gap-3">
                <div className="grid w-full gap-1.5 sm:w-44">
                    <Label htmlFor="report-period">Dates</Label>
                    <Select
                        items={report.period_options}
                        value={periodValue}
                        onValueChange={choosePeriod}
                    >
                        <SelectTrigger id="report-period" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {report.period_options.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {custom && (
                    <>
                        <div className="grid flex-1 gap-1.5 sm:w-40 sm:flex-none">
                            <Label htmlFor="report-from">From</Label>
                            <Input
                                id="report-from"
                                type="date"
                                value={dates.from}
                                onChange={(event) =>
                                    setDates({
                                        ...dates,
                                        from: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="grid flex-1 gap-1.5 sm:w-40 sm:flex-none">
                            <Label htmlFor="report-to">To</Label>
                            <Input
                                id="report-to"
                                type="date"
                                value={dates.to}
                                onChange={(event) =>
                                    setDates({
                                        ...dates,
                                        to: event.target.value,
                                    })
                                }
                            />
                        </div>
                    </>
                )}

                {report.grouping_options.length > 1 && (
                    <ToolbarSelect
                        id="report-group"
                        label="Show by"
                        options={report.grouping_options}
                        value={report.grouping}
                        onChange={(group) => visit({ ...query, group })}
                    />
                )}

                {report.offer_options && report.offer_options.length > 0 && (
                    <ToolbarSelect
                        id="report-offer"
                        label="Offer"
                        options={[
                            { value: ALL, label: 'All offers' },
                            ...report.offer_options,
                        ]}
                        value={report.offer ?? ALL}
                        onChange={(offer) =>
                            visit({
                                ...query,
                                offer: offer === ALL ? undefined : offer,
                            })
                        }
                    />
                )}

                {report.outlet_options && report.outlet_options.length > 1 && (
                    <ToolbarSelect
                        id="report-outlet"
                        label="Outlet"
                        options={[
                            { value: ALL, label: 'All outlets' },
                            ...report.outlet_options,
                        ]}
                        value={report.outlet ?? ALL}
                        onChange={(outlet) =>
                            visit({
                                ...query,
                                outlet: outlet === ALL ? undefined : outlet,
                            })
                        }
                    />
                )}

                <div className="flex items-center gap-3 sm:ml-auto">
                    {loading && <Spinner aria-label="Updating the report" />}
                    <Button
                        variant="outline"
                        nativeButton={false}
                        render={
                            <a
                                href={exportReport.url(report.key, {
                                    query,
                                })}
                            />
                        }
                    >
                        <Download data-icon="inline-start" />
                        Download CSV
                    </Button>
                </div>
            </div>

            <InputError message={errors.from ?? errors.to ?? errors.period} />
        </div>
    );
}

function ToolbarSelect({
    id,
    label,
    options,
    value,
    onChange,
}: {
    id: string;
    label: string;
    options: App.Data.Reports.ReportOptionData[];
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <div className="grid w-full gap-1.5 sm:w-44">
            <Label htmlFor={id}>{label}</Label>
            <Select
                items={options}
                value={value}
                onValueChange={(next) => next && onChange(next)}
            >
                <SelectTrigger id={id} className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
