import { Form } from '@inertiajs/react';
import type { ReactElement } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    /** The button that opens the dialog. */
    trigger: ReactElement;
    title: string;
    description?: string;
    /** Where the reason is posted, from a Wayfinder `.form()` call. */
    form: RouteFormDefinition<'post'>;
    submitLabel: string;
    destructive?: boolean;
};

/**
 * Asks for a required reason before rejecting, suspending or removing something. The reason is kept in the change log.
 */
export default function ReasonDialog({
    trigger,
    title,
    description,
    form,
    submitLabel,
    destructive = false,
}: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger render={trigger} />
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                {description && (
                    <DialogDescription>{description}</DialogDescription>
                )}

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    className="space-y-6"
                >
                    {({ processing, errors, resetAndClearErrors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="reason">Reason</Label>
                                <Textarea
                                    id="reason"
                                    name="reason"
                                    required
                                    maxLength={1000}
                                    rows={4}
                                />
                                {Object.values(errors).map((message) => (
                                    <InputError
                                        key={message}
                                        message={message}
                                    />
                                ))}
                            </div>

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
