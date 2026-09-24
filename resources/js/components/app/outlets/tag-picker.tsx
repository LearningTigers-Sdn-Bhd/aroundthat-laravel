import { Plus } from 'lucide-react';
import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import {
    Combobox,
    ComboboxChip,
    ComboboxChips,
    ComboboxChipsInput,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxItem,
    ComboboxList,
    ComboboxValue,
    useComboboxAnchor,
} from '@/components/ui/combobox';
import { Label } from '@/components/ui/label';

type TagItem = { name: string; creatable?: boolean };

type Props = {
    name: string;
    label: string;
    options: App.Data.TagOptionData[];
    defaultValue: App.Data.TagOptionData[];
    max: number;
    error?: string;
};

const sameTag = (a: string, b: string) =>
    a.trim().toLocaleLowerCase() === b.trim().toLocaleLowerCase();

/**
 * Pick tags from the shared list, or type a new one and choose “Add”. Sent as `name[]` tag names; the server
 * matches spellings to existing tags. New tags look like any other tag here.
 */
export default function TagPicker({
    name,
    label,
    options,
    defaultValue,
    max,
    error,
}: Props) {
    const id = useId();
    const anchor = useComboboxAnchor();
    const [selected, setSelected] = useState<TagItem[]>(
        defaultValue.map((tag) => ({ name: tag.name })),
    );
    const [query, setQuery] = useState('');

    const known: TagItem[] = [
        ...options.map((tag) => ({ name: tag.name })),
        ...selected.filter(
            (tag) => !options.some((option) => sameTag(option.name, tag.name)),
        ),
    ];
    const typed = query.trim();
    const items: TagItem[] =
        typed !== '' && !known.some((tag) => sameTag(tag.name, typed))
            ? [...known, { name: typed, creatable: true }]
            : known;
    const isFull = selected.length >= max;

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Combobox
                items={items}
                multiple
                value={selected}
                onValueChange={(next: TagItem[]) => {
                    setSelected(
                        next
                            .map((tag) => ({ name: tag.name }))
                            .filter(
                                (tag, index, all) =>
                                    all.findIndex((other) =>
                                        sameTag(other.name, tag.name),
                                    ) === index,
                            )
                            .slice(0, max),
                    );
                    setQuery('');
                }}
                inputValue={query}
                onInputValueChange={setQuery}
                itemToStringLabel={(tag: TagItem) => tag.name}
                isItemEqualToValue={(a: TagItem, b: TagItem) =>
                    sameTag(a.name, b.name)
                }
            >
                <ComboboxChips ref={anchor}>
                    <ComboboxValue>
                        {(value: TagItem[]) => (
                            <>
                                {value.map((tag) => (
                                    <ComboboxChip key={tag.name}>
                                        {tag.name}
                                    </ComboboxChip>
                                ))}
                                <ComboboxChipsInput
                                    id={id}
                                    aria-invalid={!!error}
                                    placeholder={
                                        isFull
                                            ? ''
                                            : value.length > 0
                                              ? 'Add another'
                                              : 'Halal, Pet friendly…'
                                    }
                                    disabled={isFull}
                                    maxLength={40}
                                />
                            </>
                        )}
                    </ComboboxValue>
                </ComboboxChips>
                <ComboboxContent anchor={anchor}>
                    <ComboboxEmpty>Type a tag to add it.</ComboboxEmpty>
                    <ComboboxList>
                        {(tag: TagItem) => (
                            <ComboboxItem key={tag.name} value={tag}>
                                {tag.creatable ? (
                                    <>
                                        <Plus />
                                        Add “{tag.name}”
                                    </>
                                ) : (
                                    tag.name
                                )}
                            </ComboboxItem>
                        )}
                    </ComboboxList>
                </ComboboxContent>
            </Combobox>

            {selected.map((tag) => (
                <input
                    key={tag.name}
                    type="hidden"
                    name={`${name}[]`}
                    value={tag.name}
                />
            ))}

            <p className="text-sm text-muted-foreground">
                Up to {max} tags. Visitors use them to find places like yours.
            </p>
            <InputError message={error} />
        </div>
    );
}
