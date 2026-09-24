import { usePage } from '@inertiajs/react';
import AlertError from '@/components/alert-error';

/**
 * Errors from an action that are not about one form field, such as "this business is suspended".
 */
export default function PageErrors({ title }: { title?: string }) {
    const errors = Object.values(
        usePage().props.errors as Record<string, string>,
    );

    return errors.length > 0 ? (
        <AlertError errors={errors} title={title} />
    ) : null;
}
