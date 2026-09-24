import type { ComponentProps } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/**
 * A labelled text input with its validation error, for use inside an Inertia `<Form>`.
 */
export default function TextField({
    name,
    label,
    error,
    ...props
}: {
    name: string;
    label: string;
    error?: string;
} & ComponentProps<typeof Input>) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={name}>{label}</Label>
            <Input id={name} name={name} aria-invalid={!!error} {...props} />
            <InputError message={error} />
        </div>
    );
}
