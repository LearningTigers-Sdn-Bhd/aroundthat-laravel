import { cn } from '@/lib/utils';
import {
    ChartNoAxesCombined,
    LayoutGrid,
    PanelLeft,
    Store,
    Tickets,
    type LucideIcon,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';

type PreviewSection = 'Offers' | 'Reports';

const navigation: { label: string; icon: LucideIcon }[] = [
    { label: 'Dashboard', icon: LayoutGrid },
    { label: 'Offers', icon: Tickets },
    { label: 'Reports', icon: ChartNoAxesCombined },
    { label: 'Outlets', icon: Store },
];

interface DashboardPreviewShellProps extends PropsWithChildren {
    activeItem: PreviewSection;
}

export function DashboardPreviewShell({
    activeItem,
    children,
}: DashboardPreviewShellProps) {
    return (
        <div className="grid min-h-[19rem] grid-cols-[3.6rem_minmax(0,1fr)] text-[0.68rem] sm:grid-cols-[8.5rem_minmax(0,1fr)]">
            <aside className="flex min-w-0 flex-col border-r bg-sidebar px-2 py-3 text-sidebar-foreground sm:px-3">
                <div className="flex items-center justify-center gap-2 sm:justify-start">
                    <AppLogoIcon
                        alt=""
                        className="size-6 shrink-0 rounded-md"
                    />
                    <div className="hidden min-w-0 sm:block">
                        <p className="truncate font-semibold">
                            Gaya Street Co.
                        </p>
                        <p className="truncate text-[0.58rem] text-muted-foreground">
                            Business workspace
                        </p>
                    </div>
                </div>

                <p className="mt-6 hidden px-2 text-[0.56rem] font-medium tracking-wide text-muted-foreground uppercase sm:block">
                    Platform
                </p>
                <ul className="mt-4 space-y-1 sm:mt-2">
                    {navigation.map((item) => {
                        const active = item.label === activeItem;

                        return (
                            <li
                                key={item.label}
                                className={cn(
                                    'flex h-8 items-center justify-center gap-2 rounded-md px-2 sm:justify-start',
                                    active
                                        ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                                        : 'text-muted-foreground',
                                )}
                            >
                                <item.icon
                                    aria-hidden="true"
                                    className="size-3.5 shrink-0"
                                />
                                <span className="hidden sm:inline">
                                    {item.label}
                                </span>
                            </li>
                        );
                    })}
                </ul>

                <div className="mt-auto flex items-center justify-center gap-2 border-t pt-3 sm:justify-start">
                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-[0.58rem] font-semibold text-primary-foreground">
                        GS
                    </span>
                    <div className="hidden min-w-0 sm:block">
                        <p className="truncate font-medium">Gaya Street Co.</p>
                        <p className="truncate text-[0.56rem] text-muted-foreground">
                            Owner
                        </p>
                    </div>
                </div>
            </aside>

            <div className="min-w-0 bg-card">
                <div className="flex h-10 items-center gap-2 border-b px-3 text-muted-foreground sm:px-4">
                    <PanelLeft aria-hidden="true" className="size-3.5" />
                    <span aria-hidden="true" className="text-border">
                        /
                    </span>
                    <span className="font-medium text-foreground">
                        {activeItem}
                    </span>
                </div>
                <div className="p-3 sm:p-4">{children}</div>
            </div>
        </div>
    );
}
