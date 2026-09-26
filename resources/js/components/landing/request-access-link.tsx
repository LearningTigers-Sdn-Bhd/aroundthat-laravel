import { cn } from '@/lib/utils';
import { ArrowRight } from 'lucide-react';

import { accessRequestHref } from '@/lib/brand';

interface RequestAccessLinkProps {
    className?: string;
    label?: string;
}

export function RequestAccessLink({
    className,
    label = 'Request access',
}: RequestAccessLinkProps) {
    return (
        <a
            href={accessRequestHref}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/85 focus-visible:ring-3 focus-visible:ring-ring/40 focus-visible:outline-none',
                className,
            )}
        >
            {label}
            <ArrowRight className="size-4" aria-hidden />
        </a>
    );
}
