import { Link } from '@inertiajs/react';
import { LogOut, Wifi, WifiOff } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

/**
 * The business, whether the counter is online, the outlet, and the way back to the app.
 */
export default function CounterHeader({
    businessName,
    online,
    outletPicker,
}: {
    businessName: string;
    online: boolean;
    outletPicker?: ReactNode;
}) {
    return (
        <header className="flex flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-8">
            <div className="min-w-0">
                <p className="truncate text-base font-semibold">
                    {businessName}
                </p>
                <div
                    className={cn(
                        'mt-1 flex items-center gap-1.5 text-xs',
                        online ? 'text-primary' : 'text-destructive',
                    )}
                >
                    {online ? (
                        <Wifi className="size-3.5" />
                    ) : (
                        <WifiOff className="size-3.5" />
                    )}
                    {online ? 'Online' : 'Offline · network required'}
                </div>
            </div>
            <div className="flex items-center gap-2">
                {outletPicker}
                <Button
                    variant="ghost"
                    nativeButton={false}
                    render={<Link href={dashboard()} />}
                >
                    <LogOut data-icon="inline-start" />
                    Exit counter
                </Button>
            </div>
        </header>
    );
}
