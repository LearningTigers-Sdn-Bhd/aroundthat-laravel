import type { ReactNode } from 'react';

/**
 * A red banner explaining why a record is suspended or rejected.
 */
export default function Notice({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
            <p className="font-medium">{title}</p>
            <p className="mt-1">{children}</p>
        </div>
    );
}
