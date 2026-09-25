import { Link } from '@inertiajs/react';
import { ChevronRight, CircleAlert } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

/**
 * What the member could deal with next, each linking to where it is done. Renders nothing when all is done.
 */
export default function AttentionList({
    items,
}: {
    items: App.Data.DashboardAttentionData[];
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <Card size="sm">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <CircleAlert className="size-4 text-amber-600 dark:text-amber-400" />
                    Needs attention
                </CardTitle>
                <CardDescription>
                    {items.length === 1
                        ? 'One thing left to do.'
                        : `${items.length} things left to do.`}
                </CardDescription>
            </CardHeader>
            <CardContent className="px-0">
                <ul className="divide-y">
                    {items.map((item) => (
                        <li key={item.title}>
                            <Link
                                href={item.url}
                                prefetch
                                className="flex items-center gap-3 px-(--card-spacing) py-2.5 transition-colors hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">{item.title}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {item.description}
                                    </p>
                                </div>
                                <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                            </Link>
                        </li>
                    ))}
                </ul>
            </CardContent>
        </Card>
    );
}
