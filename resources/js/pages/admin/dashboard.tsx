import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import {
    index as businessesIndex,
    show as showBusiness,
} from '@/routes/admin/businesses';

type Props = {
    pendingBusinessCount: number;
    pendingOutlets: App.Data.Admin.OutletData[];
};

export default function AdminDashboard({
    pendingBusinessCount,
    pendingOutlets,
}: Props) {
    return (
        <>
            <Head title="Admin" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Waiting for review"
                    description="Businesses and outlets their owners have submitted."
                />

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Businesses</CardTitle>
                            <CardDescription>
                                {pendingBusinessCount === 1
                                    ? '1 business is waiting for review.'
                                    : `${pendingBusinessCount} businesses are waiting for review.`}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button variant="outline" asChild>
                                <Link
                                    href={businessesIndex({
                                        query: {
                                            filter: {
                                                onboarding_status: 'pending',
                                            },
                                        },
                                    })}
                                >
                                    Review businesses
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Outlets</CardTitle>
                            <CardDescription>
                                Oldest submissions first.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {pendingOutlets.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No outlets are waiting for review.
                                </p>
                            ) : (
                                <ul className="divide-y text-sm">
                                    {pendingOutlets.map((outlet) => (
                                        <li
                                            key={outlet.id}
                                            className="flex items-center justify-between gap-4 py-2"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {outlet.name}
                                                </p>
                                                <Link
                                                    href={showBusiness(
                                                        outlet.business_id,
                                                    )}
                                                    className="text-muted-foreground hover:underline"
                                                >
                                                    {outlet.business_name}
                                                </Link>
                                            </div>
                                            <span className="text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    outlet.submitted_at,
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Admin', href: dashboard() }],
};
