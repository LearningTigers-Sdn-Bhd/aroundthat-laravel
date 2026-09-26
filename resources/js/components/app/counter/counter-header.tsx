import { Wifi, WifiOff } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * The business and whether the counter is online, with the outlet picker on its own full-width row below.
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
        <header className="space-y-3">
            <div className="flex items-center justify-between gap-4">
                <p className="min-w-0 truncate text-base font-semibold">
                    {businessName}
                </p>
                <p
                    className={cn(
                        'flex shrink-0 items-center gap-1.5 text-xs',
                        online ? 'text-primary' : 'text-destructive',
                    )}
                >
                    {online ? (
                        <Wifi className="size-3.5" />
                    ) : (
                        <WifiOff className="size-3.5" />
                    )}
                    {online ? 'Online' : 'Offline'}
                </p>
            </div>
            {outletPicker}
        </header>
    );
}
