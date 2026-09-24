import { Form } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Spinner } from '@/components/ui/spinner';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    /** The button that opens the dialog. */
    trigger: ReactElement;
    title: string;
    description?: string;
    /** Where the confirmation is posted, from a Wayfinder `.form()` call. */
    form: RouteFormDefinition<'post'>;
    confirmLabel: string;
    destructive?: boolean;
};

/**
 * Asks the user to confirm an action that has no fields, such as removing a member.
 * Unlike a dialog, it does not close on an outside click, so the user must choose.
 */
export default function ConfirmDialog({
    trigger,
    title,
    description,
    form,
    confirmLabel,
    destructive = false,
}: Props) {
    const [open, setOpen] = useState(false);

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger render={trigger} />
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    {description && (
                        <AlertDialogDescription>
                            {description}
                        </AlertDialogDescription>
                    )}
                </AlertDialogHeader>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            {Object.values(errors).map((message) => (
                                <InputError key={message} message={message} />
                            ))}

                            <AlertDialogFooter>
                                <AlertDialogCancel type="button">
                                    Cancel
                                </AlertDialogCancel>
                                <AlertDialogAction
                                    type="submit"
                                    variant={
                                        destructive ? 'destructive' : 'default'
                                    }
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {confirmLabel}
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </>
                    )}
                </Form>
            </AlertDialogContent>
        </AlertDialog>
    );
}
