import InputError from '@/components/input-error';
import {
    Combobox,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
} from '@/components/ui/combobox';
import { Label } from '@/components/ui/label';

export type ComboboxOption = { value: string; label: string };

type Props = {
    name: string;
    label: string;
    options: ComboboxOption[];
    defaultValue?: string | null;
    /** Called with the chosen option's value, or null when cleared. */
    onValueChange?: (value: string | null) => void;
    placeholder?: string;
    error?: string;
    required?: boolean;
};

/**
 * A labelled, searchable select with its validation error, for use inside an Inertia `<Form>`.
 */
export default function ComboboxField({
    name,
    label,
    options,
    defaultValue,
    onValueChange,
    placeholder,
    error,
    required,
}: Props) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={name}>{label}</Label>
            <Combobox
                items={options}
                name={name}
                required={required}
                defaultValue={
                    options.find((option) => option.value === defaultValue) ??
                    null
                }
                itemToStringLabel={(option: ComboboxOption) => option.label}
                itemToStringValue={(option: ComboboxOption) => option.value}
                onValueChange={(option: ComboboxOption | null) =>
                    onValueChange?.(option?.value ?? null)
                }
            >
                <ComboboxInput
                    id={name}
                    placeholder={placeholder}
                    aria-invalid={!!error}
                    className="w-full"
                />
                <ComboboxContent>
                    <ComboboxEmpty>No matches.</ComboboxEmpty>
                    <ComboboxList>
                        {(option: ComboboxOption) => (
                            <ComboboxItem key={option.value} value={option}>
                                {option.label}
                            </ComboboxItem>
                        )}
                    </ComboboxList>
                </ComboboxContent>
            </Combobox>
            <InputError message={error} />
        </div>
    );
}
