import { Head, Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import Heading from '@/components/heading';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index, show } from '@/routes/reports';

type Props = {
    reports: App.Data.Reports.ReportSummaryData[];
};

export default function ReportsIndex({ reports }: Props) {
    return (
        <>
            <Head title="Reports" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Reports"
                    description="See how your offers and outlets are doing. Each report answers one question."
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {reports.map((report) => (
                        <Link
                            key={report.key}
                            href={show(report.key)}
                            prefetch
                            className="rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Card className="h-full transition-colors hover:bg-muted/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between gap-2">
                                        {report.title}
                                        <ChevronRight className="size-4 text-muted-foreground" />
                                    </CardTitle>
                                    <CardDescription>
                                        {report.question}
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}

ReportsIndex.layout = {
    breadcrumbs: [{ title: 'Reports', href: index() }],
};
