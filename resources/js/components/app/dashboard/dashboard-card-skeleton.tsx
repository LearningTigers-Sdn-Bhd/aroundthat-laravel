import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

/**
 * Stands in for a dashboard list card while its deferred rows load.
 */
export function DashboardCardSkeleton() {
    return (
        <Card size="sm">
            <CardHeader>
                <Skeleton className="h-4 w-40" />
                <Skeleton className="h-3 w-56" />
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                {Array.from({ length: 4 }, (_, index) => (
                    <div key={index} className="flex items-center gap-3">
                        <div className="flex flex-1 flex-col gap-1.5">
                            <Skeleton className="h-4 w-2/3" />
                            <Skeleton className="h-3 w-1/2" />
                        </div>
                        <Skeleton className="h-5 w-16" />
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
