import { usePage } from '@inertiajs/react';
import AlertError from '@/components/alert-error';

/**
 * Errors from an action that are not about one form field, such as "this business is suspended".
 */
export default function PageErrors({
    title,
    except = [],
}: {
    title?: string;
    /** Fields whose errors the page already shows next to the input. */
    except?: string[];
}) {
    const errors = Object.entries(
        usePage().props.errors as Record<string, string>,
    )
        .filter(([field]) => !except.includes(field))
        .map(([, message]) => message);

    return errors.length > 0 ? (
        <AlertError errors={errors} title={title} />
    ) : null;
}
