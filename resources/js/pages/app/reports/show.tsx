import { Head, setLayoutProps } from '@inertiajs/react';
import { ChartNoAxesColumn } from 'lucide-react';
import Heading from '@/components/heading';
import ReportChart from '@/components/reports/report-chart';
import ReportTable from '@/components/reports/report-table';
import ReportTiles from '@/components/reports/report-tiles';
import ReportToolbar from '@/components/reports/report-toolbar';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { index, show } from '@/routes/reports';

type Props = {
    report: App.Data.Reports.ReportPageData;
};

export default function ReportShow({ report }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Reports', href: index() },
            { title: report.title, href: show(report.key) },
        ],
    });

    return (
        <>
            <Head title={report.title} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading title={report.title} description={report.question} />

                <ReportToolbar report={report} />

                <p className="text-sm text-muted-foreground">
                    {report.period_label}
                </p>

                <ReportTiles tiles={report.tiles} currency={report.currency} />

                {report.rows.length === 0 ? (
                    <Empty className="border">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <ChartNoAxesColumn />
                            </EmptyMedia>
                            <EmptyTitle>Nothing in these dates</EmptyTitle>
                            <EmptyDescription>
                                Try a longer range, or clear the offer and
                                outlet choices.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <>
                        {report.chart_series.length > 0 && (
                            <ReportChart
                                series={report.chart_series}
                                columns={report.columns}
                                rows={report.rows}
                            />
                        )}
                        <ReportTable
                            columns={report.columns}
                            rows={report.rows}
                            currency={report.currency}
                        />
                    </>
                )}
            </div>
        </>
    );
}
