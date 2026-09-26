import type { ReactNode } from 'react';

/**
 * One labelled value in a `<dl>`. Empty values show "—".
 */
export default function Detail({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="mt-0.5 whitespace-pre-line">{children || '—'}</dd>
        </div>
    );
}
