import { cn } from '@/lib/utils';
import type { PropsWithChildren } from 'react';

interface MacWindowFrameProps extends PropsWithChildren {
    title: string;
    className?: string;
    contentClassName?: string;
}

export function MacWindowFrame({
    title,
    className,
    contentClassName,
    children,
}: MacWindowFrameProps) {
    return (
        <div
            className={cn(
                'overflow-hidden rounded-xl border bg-card shadow-[var(--landing-window-shadow)]',
                className,
            )}
        >
            <div className="relative flex h-9 items-center justify-center border-b bg-landing-window-chrome px-12">
                <div
                    aria-hidden="true"
                    className="absolute left-3 flex items-center gap-1.5"
                >
                    <span className="size-2.5 rounded-full bg-[#EF6A5F]" />
                    <span className="size-2.5 rounded-full bg-[#E7B84B]" />
                    <span className="size-2.5 rounded-full bg-[#61C454]" />
                </div>
                <p className="truncate text-[0.68rem] font-medium text-muted-foreground">
                    {title}
                </p>
            </div>
            <div className={cn('min-h-0 bg-background', contentClassName)}>
                {children}
            </div>
        </div>
    );
}
