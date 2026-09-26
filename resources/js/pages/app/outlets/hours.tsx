import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import type { FormEvent } from 'react';
import OutletPage from '@/components/app/outlets/outlet-page';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PageErrors from '@/components/page-errors';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit, index } from '@/routes/outlets';
import { edit as editHours, update } from '@/routes/outlets/hours';

type Period = App.Data.OpeningPeriodData;

type DateException = {
    date: string;
    is_closed: boolean;
    periods: Period[];
    note: string;
};

type Props = {
    outlet: App.Data.OutletData;
    recentReverts: App.Data.RevertNoticeData[];
    hours: App.Data.OutletHoursData;
    today: string;
    can: { update: boolean; submit: boolean; archive: boolean };
};

const days = [
    ['1', 'Monday'],
    ['2', 'Tuesday'],
    ['3', 'Wednesday'],
    ['4', 'Thursday'],
    ['5', 'Friday'],
    ['6', 'Saturday'],
    ['7', 'Sunday'],
] as const;

const newPeriod = (): Period => ({ opens: '09:00', closes: '17:00' });
const allDay = (): Period[] => [{ opens: '00:00', closes: '00:00' }];

export default function OutletHours({
    outlet,
    recentReverts,
    hours,
    today,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Outlets', href: index() },
            { title: outlet.name, href: edit(outlet.id) },
            { title: 'Hours', href: editHours(outlet.id) },
        ],
    });

    const form = useForm<{
        regular_hours: Record<string, Period[]>;
        date_exceptions: DateException[];
    }>({
        regular_hours: hours.regular_hours,
        date_exceptions: hours.date_exceptions.map((exception) => ({
            ...exception,
            note: exception.note ?? '',
        })),
    });
    const errors = form.errors as Record<string, string>;

    const setDay = (day: string, periods: Period[]) =>
        form.setData('regular_hours', {
            ...form.data.regular_hours,
            [day]: periods,
        });

    const setException = (position: number, changes: Partial<DateException>) =>
        form.setData(
            'date_exceptions',
            form.data.date_exceptions.map((exception, index) =>
                index === position ? { ...exception, ...changes } : exception,
            ),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(outlet.id), { preserveScroll: true });
    };

    return (
        <>
            <Head title={`${outlet.name} · Hours`} />

            <OutletPage outlet={outlet} can={can} recentReverts={recentReverts}>
                <PageErrors
                    except={Object.keys(errors).filter(
                        (key) =>
                            key.startsWith('regular_hours') ||
                            key.startsWith('date_exceptions'),
                    )}
                />

                <form onSubmit={submit}>
                    <fieldset
                        disabled={!can.update}
                        className="space-y-8 disabled:opacity-60"
                    >
                        <section className="space-y-4">
                            <Heading
                                variant="small"
                                title="Weekly hours"
                                description={`In the outlet's time zone, ${hours.timezone}. For hours past midnight, close at 00:00 and open again at 00:00 the next day.`}
                            />
                            <InputError message={errors.regular_hours} />

                            <div className="divide-y rounded-md border">
                                {days.map(([day, name]) => (
                                    <DayRow
                                        key={day}
                                        name={name}
                                        periods={
                                            form.data.regular_hours[day] ?? []
                                        }
                                        onChange={(periods) =>
                                            setDay(day, periods)
                                        }
                                        error={errors[`regular_hours.${day}`]}
                                    />
                                ))}
                            </div>
                        </section>

                        <section className="space-y-4">
                            <Heading
                                variant="small"
                                title="Special dates"
                                description="Public holidays and other days with different hours. They replace the weekly hours on that date."
                            />
                            <InputError message={errors.date_exceptions} />

                            {form.data.date_exceptions.map(
                                (exception, position) => (
                                    <div
                                        key={position}
                                        className="space-y-3 rounded-md border p-4"
                                    >
                                        <div className="flex flex-wrap items-end gap-3">
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`exception-${position}-date`}
                                                >
                                                    Date
                                                </Label>
                                                <Input
                                                    id={`exception-${position}-date`}
                                                    type="date"
                                                    min={today}
                                                    value={exception.date}
                                                    onChange={(event) =>
                                                        setException(position, {
                                                            date: event.target
                                                                .value,
                                                        })
                                                    }
                                                    required
                                                    className="w-auto"
                                                />
                                            </div>
                                            <div className="grid flex-1 gap-2">
                                                <Label
                                                    htmlFor={`exception-${position}-note`}
                                                >
                                                    Note
                                                </Label>
                                                <Input
                                                    id={`exception-${position}-note`}
                                                    value={exception.note}
                                                    maxLength={120}
                                                    placeholder="Hari Raya Aidilfitri"
                                                    onChange={(event) =>
                                                        setException(position, {
                                                            note: event.target
                                                                .value,
                                                        })
                                                    }
                                                />
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Remove this date"
                                                onClick={() =>
                                                    form.setData(
                                                        'date_exceptions',
                                                        form.data.date_exceptions.filter(
                                                            (_, index) =>
                                                                index !==
                                                                position,
                                                        ),
                                                    )
                                                }
                                            >
                                                <X />
                                            </Button>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                id={`exception-${position}-closed`}
                                                checked={exception.is_closed}
                                                onCheckedChange={(checked) =>
                                                    setException(position, {
                                                        is_closed: checked,
                                                        periods: checked
                                                            ? []
                                                            : [newPeriod()],
                                                    })
                                                }
                                            />
                                            <Label
                                                htmlFor={`exception-${position}-closed`}
                                            >
                                                Closed all day
                                            </Label>
                                        </div>

                                        {!exception.is_closed && (
                                            <Periods
                                                periods={exception.periods}
                                                onChange={(periods) =>
                                                    setException(position, {
                                                        periods,
                                                    })
                                                }
                                            />
                                        )}

                                        <InputError
                                            message={
                                                errors[
                                                    `date_exceptions.${position}.date`
                                                ] ??
                                                errors[
                                                    `date_exceptions.${position}.periods`
                                                ] ??
                                                errors[
                                                    `date_exceptions.${position}.note`
                                                ]
                                            }
                                        />
                                    </div>
                                ),
                            )}

                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    form.setData('date_exceptions', [
                                        ...form.data.date_exceptions,
                                        {
                                            date: today,
                                            is_closed: true,
                                            periods: [],
                                            note: '',
                                        },
                                    ])
                                }
                            >
                                <Plus />
                                Add a date
                            </Button>
                        </section>

                        {can.update && (
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />}
                                    Save
                                </Button>
                            </div>
                        )}
                    </fieldset>
                </form>
            </OutletPage>
        </>
    );
}

/**
 * One weekday: closed, open all day, or a list of periods.
 */
function DayRow({
    name,
    periods,
    onChange,
    error,
}: {
    name: string;
    periods: Period[];
    onChange: (periods: Period[]) => void;
    error?: string;
}) {
    const isOpenAllDay =
        periods.length === 1 &&
        periods[0].opens === '00:00' &&
        periods[0].closes === '00:00';

    return (
        <div className="flex flex-col gap-3 p-4 sm:flex-row sm:items-start">
            <div className="w-28 pt-2 text-sm font-medium">{name}</div>
            <div className="flex-1 space-y-3">
                {periods.length === 0 ? (
                    <p className="pt-2 text-sm text-muted-foreground">Closed</p>
                ) : isOpenAllDay ? (
                    <p className="pt-2 text-sm">Open 24 hours</p>
                ) : (
                    <Periods periods={periods} onChange={onChange} />
                )}
                <div className="flex flex-wrap gap-2">
                    {periods.length === 0 || isOpenAllDay ? (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => onChange([newPeriod()])}
                        >
                            Set hours
                        </Button>
                    ) : null}
                    {!isOpenAllDay && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => onChange(allDay())}
                        >
                            Open 24 hours
                        </Button>
                    )}
                    {periods.length > 0 && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => onChange([])}
                        >
                            Closed
                        </Button>
                    )}
                </div>
                <InputError message={error} />
            </div>
        </div>
    );
}

/**
 * Opening and closing time pairs, with buttons to add and remove a period.
 */
function Periods({
    periods,
    onChange,
}: {
    periods: Period[];
    onChange: (periods: Period[]) => void;
}) {
    const setPeriod = (position: number, changes: Partial<Period>) =>
        onChange(
            periods.map((period, index) =>
                index === position ? { ...period, ...changes } : period,
            ),
        );

    return (
        <div className="space-y-2">
            {periods.map((period, position) => (
                <div key={position} className="flex items-center gap-2">
                    <Input
                        type="time"
                        aria-label="Opens"
                        value={period.opens}
                        onChange={(event) =>
                            setPeriod(position, { opens: event.target.value })
                        }
                        required
                        className="w-32"
                    />
                    <span className="text-muted-foreground">to</span>
                    <Input
                        type="time"
                        aria-label="Closes"
                        value={period.closes}
                        onChange={(event) =>
                            setPeriod(position, { closes: event.target.value })
                        }
                        required
                        className="w-32"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Remove this period"
                        onClick={() =>
                            onChange(
                                periods.filter(
                                    (_, index) => index !== position,
                                ),
                            )
                        }
                    >
                        <X />
                    </Button>
                </div>
            ))}
            <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => onChange([...periods, newPeriod()])}
            >
                <Plus />
                Add a period
            </Button>
        </div>
    );
}
