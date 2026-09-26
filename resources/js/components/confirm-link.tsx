import { Link, router } from '@inertiajs/react';
import type { ComponentProps, MouseEvent } from 'react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

export type LinkConfirmation = {
    title: string;
    description?: string;
    confirmLabel: string;
};

type Props = Omit<ComponentProps<typeof Link>, 'href'> & {
    href: NonNullable<ComponentProps<typeof Link>['href']>;
    confirmation: LinkConfirmation;
};

/**
 * An Inertia link that asks the user to confirm before it visits, so a stray click does not leave the page.
 * A modified click (new tab, new window) is left to the browser.
 */
export default function ConfirmLink({
    confirmation,
    href,
    onClick,
    ...props
}: Props) {
    const [open, setOpen] = useState(false);

    const handleClick = (event: MouseEvent) => {
        onClick?.(event);

        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        event.preventDefault();
        setOpen(true);
    };

    const confirm = () => {
        setOpen(false);
        router.visit(href);
    };

    return (
        <>
            <Link href={href} onClick={handleClick} {...props} />
            <AlertDialog open={open} onOpenChange={setOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {confirmation.title}
                        </AlertDialogTitle>
                        {confirmation.description && (
                            <AlertDialogDescription>
                                {confirmation.description}
                            </AlertDialogDescription>
                        )}
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={confirm}>
                            {confirmation.confirmLabel}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
