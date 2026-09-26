import { Form } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { RouteFormDefinition } from '@/wayfinder';

/**
 * A button that posts to one route with no fields, such as "Approve" or "Resend".
 */
export default function ActionButton({
    form,
    children,
    ...props
}: {
    /** From a Wayfinder `.form()` call. */
    form: RouteFormDefinition<'post'>;
} & Omit<ComponentProps<typeof Button>, 'type' | 'form'>) {
    return (
        <Form {...form} options={{ preserveScroll: true }}>
            {({ processing }) => (
                <Button type="submit" disabled={processing} {...props}>
                    {processing && <Spinner />}
                    {children}
                </Button>
            )}
        </Form>
    );
}
