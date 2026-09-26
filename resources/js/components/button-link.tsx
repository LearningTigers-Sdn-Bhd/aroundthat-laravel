import { Link } from '@inertiajs/react';
import type { VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = Omit<ComponentProps<typeof Link>, 'size'> &
    VariantProps<typeof buttonVariants>;

/**
 * An Inertia link styled as a button. The label goes in `children`, and the
 * element keeps its link semantics instead of Base UI's `role="button"`.
 */
export default function ButtonLink({
    className,
    variant,
    size,
    children,
    ...props
}: Props) {
    return (
        <Link
            data-slot="button"
            className={cn(buttonVariants({ variant, size, className }))}
            {...props}
        >
            {children}
        </Link>
    );
}
