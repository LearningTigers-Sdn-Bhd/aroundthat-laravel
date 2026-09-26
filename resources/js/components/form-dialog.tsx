import { Form } from '@inertiajs/react';
import type { ReactElement, ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    /** The button that opens the dialog. */
    trigger: ReactElement;
    title: string;
    description?: string;
    /** Where the form is posted, from a Wayfinder `.form()` call. */
    form: RouteFormDefinition<'post'>;
    submitLabel: string;
    destructive?: boolean;
    /** The fields, given the current errors. */
    children: (errors: Record<string, string>) => ReactNode;
};

/**
 * A dialog holding a small form, such as an invitation. See {@link ConfirmDialog} for a confirmation with no fields.
 */
export default function FormDialog({
    trigger,
    title,
    description,
    form,
    submitLabel,
    destructive = false,
    children,
}: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger render={trigger} />
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="space-y-6"
                >
                    {({ processing, errors, resetAndClearErrors }) => (
                        <>
                            {children(errors)}

                            <DialogFooter className="gap-2">
                                <DialogClose
                                    render={
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() =>
                                                resetAndClearErrors()
                                            }
                                        />
                                    }
                                >
                                    Cancel
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant={
                                        destructive ? 'destructive' : 'default'
                                    }
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {submitLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
