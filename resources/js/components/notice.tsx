import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

const tones = {
    danger: 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200',
    info: 'bg-muted/50 text-foreground',
} as const;

/**
 * A banner explaining why a record is suspended, rejected or hidden (red), or something an admin did to it (info).
 */
export default function Notice({
    title,
    children,
    tone = 'danger',
}: {
    title: string;
    children: ReactNode;
    tone?: keyof typeof tones;
}) {
    return (
        <div className={cn('rounded-md border p-4 text-sm', tones[tone])}>
            <p className="font-medium">{title}</p>
            <p className="mt-1">{children}</p>
        </div>
    );
}
