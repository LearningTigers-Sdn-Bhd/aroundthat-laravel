import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import type { ReportValue } from '@/lib/reports';

type Props = {
    series: string[];
    columns: App.Support.Reports.ReportColumn[];
    rows: Record<string, ReportValue>[];
};

/**
 * The report's day, week or month rows as bars, one colour per series, named like their table columns.
 */
export default function ReportChart({ series, columns, rows }: Props) {
    const config: ChartConfig = Object.fromEntries(
        series.map((key, index) => [
            key,
            {
                label:
                    columns.find((column) => column.key === key)?.label ?? key,
                color: `var(--chart-${index + 1})`,
            },
        ]),
    );

    return (
        <div className="rounded-md border p-4">
            <ChartContainer config={config} className="aspect-auto h-56 w-full">
                <BarChart accessibilityLayer data={rows}>
                    <CartesianGrid vertical={false} />
                    <XAxis
                        dataKey="label"
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        minTickGap={16}
                    />
                    <YAxis
                        allowDecimals={false}
                        tickLine={false}
                        axisLine={false}
                        width={32}
                    />
                    <ChartTooltip content={<ChartTooltipContent />} />
                    {series.length > 1 && (
                        <ChartLegend content={<ChartLegendContent />} />
                    )}
                    {series.map((key) => (
                        <Bar
                            key={key}
                            dataKey={key}
                            fill={`var(--color-${key})`}
                            radius={4}
                        />
                    ))}
                </BarChart>
            </ChartContainer>
        </div>
    );
}
