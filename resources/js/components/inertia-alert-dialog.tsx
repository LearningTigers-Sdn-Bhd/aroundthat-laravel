import { HeadlessModal } from '@inertiaui/modal-react';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    type InertiaModalRenderProps,
    useInertiaModal,
} from '@/hooks/use-inertia-modal';

interface InertiaAlertDialogProps {
    name?: string;
    title: ReactNode;
    description?: ReactNode;
    size?: 'default' | 'sm';
    className?: string;
    children: ReactNode | ((modal: InertiaModalRenderProps) => ReactNode);
}

/**
 * An InertiaUI modal rendered as a shadcn AlertDialog. See {@link InertiaSheet}.
 *
 * Base UI's AlertDialog omits `modal` and `disablePointerDismissal` by design —
 * it is always modal and never dismissed by an outside press — so only the
 * shared root props apply here. Escape still honours `closeExplicitly`.
 */
export default function InertiaAlertDialog({
    name,
    ...props
}: InertiaAlertDialogProps) {
    return (
        <HeadlessModal name={name}>
            {(modal: InertiaModalRenderProps) => (
                <InertiaAlertDialogPanel {...props} modal={modal} />
            )}
        </HeadlessModal>
    );
}

function InertiaAlertDialogPanel({
    title,
    description,
    size = 'default',
    className,
    children,
    modal,
}: InertiaAlertDialogProps & { modal: InertiaModalRenderProps }) {
    const { rootProps, contentProps } = useInertiaModal(modal);

    return (
        <AlertDialog {...rootProps}>
            <AlertDialogContent
                {...contentProps}
                size={size}
                className={cn('data-[inactive]:blur-xs', className)}
            >
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    {description ? (
                        <AlertDialogDescription>
                            {description}
                        </AlertDialogDescription>
                    ) : null}
                </AlertDialogHeader>
                {typeof children === 'function' ? children(modal) : children}
            </AlertDialogContent>
        </AlertDialog>
    );
}
