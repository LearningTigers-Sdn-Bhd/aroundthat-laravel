import { ModalLink } from '@inertiaui/modal-react';
import type { VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = ComponentProps<typeof ModalLink> &
    VariantProps<typeof buttonVariants> & { className?: string };

/**
 * A link that opens its page as a routed modal, styled as a button. See {@link ButtonLink}.
 * It opens as a slideover and puts the modal's URL in the address bar, so the
 * modal can be bookmarked and closed with the back button.
 */
export default function ModalButtonLink({
    className,
    variant,
    size,
    children,
    ...props
}: Props) {
    return (
        <ModalLink
            navigate
            slideover
            data-slot="button"
            className={cn(buttonVariants({ variant, size, className }))}
            {...props}
        >
            {children}
        </ModalLink>
    );
}
