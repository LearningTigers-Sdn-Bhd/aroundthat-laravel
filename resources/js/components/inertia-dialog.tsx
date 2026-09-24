import { HeadlessModal } from '@inertiaui/modal-react';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    type InertiaModalRenderProps,
    useInertiaModal,
} from '@/hooks/use-inertia-modal';

interface InertiaDialogProps {
    title: ReactNode;
    description?: ReactNode;
    className?: string;
    children: ReactNode | ((modal: InertiaModalRenderProps) => ReactNode);
}

/** A routed InertiaUI modal rendered as a shadcn Dialog. See {@link InertiaSheet}. */
export default function InertiaDialog(props: InertiaDialogProps) {
    return (
        <HeadlessModal>
            {(modal: InertiaModalRenderProps) => (
                <InertiaDialogPanel {...props} modal={modal} />
            )}
        </HeadlessModal>
    );
}

function InertiaDialogPanel({
    title,
    description,
    className,
    children,
    modal,
}: InertiaDialogProps & { modal: InertiaModalRenderProps }) {
    const { rootProps, dismissProps, contentProps, showCloseButton } =
        useInertiaModal(modal);

    return (
        <Dialog {...rootProps} {...dismissProps}>
            <DialogContent
                {...contentProps}
                showCloseButton={showCloseButton}
                className={cn('data-[inactive]:blur-xs', className)}
            >
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description ? (
                        <DialogDescription>{description}</DialogDescription>
                    ) : null}
                </DialogHeader>
                {typeof children === 'function' ? children(modal) : children}
            </DialogContent>
        </Dialog>
    );
}
