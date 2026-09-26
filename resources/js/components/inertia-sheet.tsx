import { HeadlessModal } from '@inertiaui/modal-react';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    type InertiaModalRenderProps,
    useInertiaModal,
} from '@/hooks/use-inertia-modal';

interface InertiaSheetProps {
    title: ReactNode;
    description?: ReactNode;
    side?: 'top' | 'right' | 'bottom' | 'left';
    className?: string;
    children: ReactNode | ((modal: InertiaModalRenderProps) => ReactNode);
}

/**
 * A routed InertiaUI modal rendered as a shadcn Sheet.
 *
 * `HeadlessModal` contributes the modal state and keeps the package's routing
 * (history, base URL, redirects) intact; every pixel of the panel is ours.
 */
export default function InertiaSheet(props: InertiaSheetProps) {
    return (
        <HeadlessModal>
            {(modal: InertiaModalRenderProps) => (
                <InertiaSheetPanel {...props} modal={modal} />
            )}
        </HeadlessModal>
    );
}

function InertiaSheetPanel({
    title,
    description,
    side = 'right',
    className,
    children,
    modal,
}: InertiaSheetProps & { modal: InertiaModalRenderProps }) {
    const { rootProps, dismissProps, contentProps, showCloseButton } =
        useInertiaModal(modal);

    return (
        <Sheet {...rootProps} {...dismissProps}>
            <SheetContent
                {...contentProps}
                side={side}
                showCloseButton={showCloseButton}
                className={cn('data-[inactive]:blur-xs', className)}
            >
                <SheetHeader>
                    <SheetTitle>{title}</SheetTitle>
                    {description ? (
                        <SheetDescription>{description}</SheetDescription>
                    ) : null}
                </SheetHeader>
                {typeof children === 'function' ? children(modal) : children}
            </SheetContent>
        </Sheet>
    );
}
