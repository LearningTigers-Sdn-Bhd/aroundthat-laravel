import { useState } from 'react';
import InputError from '@/components/input-error';
import TextField from '@/components/text-field';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

/** The inputs OfferFields renders, so a page can leave their errors to it. */
export const offerFieldNames = [
    'name',
    'description',
    'discount_type',
    'discount_value',
    'max_discount_amount',
    'min_spend_amount',
    'free_item',
    'starts_at',
    'ends_at',
    'voucher_valid_days',
    'voucher_limit',
    'uses_per_voucher',
];

const discountTypes: { value: App.Enums.DiscountType; label: string }[] = [
    { value: 'percentage', label: 'Percentage off' },
    { value: 'amount', label: 'Amount off' },
    { value: 'free_item', label: 'Free item' },
];

/**
 * An offer's terms, for use inside an Inertia `<Form>`. Pass `offer` to prefill an edit form.
 */
export default function OfferFields({
    errors,
    offer,
    currency,
    timezone,
}: {
    errors: Record<string, string>;
    offer?: App.Data.OfferData;
    currency: string;
    timezone: string;
}) {
    const [discountType, setDiscountType] = useState<App.Enums.DiscountType>(
        offer?.discount_type ?? 'percentage',
    );

    return (
        <>
            <TextField
                name="name"
                label="Name"
                defaultValue={offer?.name}
                error={errors.name}
                placeholder="10% off dinner"
                required
            />
            <DescriptionField
                defaultValue={offer?.description}
                error={errors.description}
            />

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="discount_type">Discount</Label>
                    <Select
                        name="discount_type"
                        items={discountTypes}
                        value={discountType}
                        onValueChange={(value) => {
                            if (value) {
                                setDiscountType(value);
                            }
                        }}
                    >
                        <SelectTrigger id="discount_type" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {discountTypes.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.discount_type} />
                </div>

                {discountType === 'free_item' ? (
                    <TextField
                        name="free_item"
                        label="Free item"
                        defaultValue={offer?.free_item ?? ''}
                        error={errors.free_item}
                        placeholder="Iced latte"
                        required
                    />
                ) : (
                    <TextField
                        name="discount_value"
                        label={
                            discountType === 'percentage'
                                ? 'Percentage'
                                : `Amount (${currency})`
                        }
                        type="number"
                        step="0.01"
                        min="0.01"
                        max={discountType === 'percentage' ? 100 : undefined}
                        defaultValue={offer?.discount_value ?? ''}
                        error={errors.discount_value}
                        required
                    />
                )}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                {discountType === 'percentage' && (
                    <TextField
                        name="max_discount_amount"
                        label={`Most off per bill (${currency})`}
                        type="number"
                        step="0.01"
                        min="0.01"
                        defaultValue={offer?.max_discount_amount ?? ''}
                        error={errors.max_discount_amount}
                    />
                )}
                <TextField
                    name="min_spend_amount"
                    label={`Minimum spend (${currency})`}
                    type="number"
                    step="0.01"
                    min="0.01"
                    defaultValue={offer?.min_spend_amount ?? ''}
                    error={errors.min_spend_amount}
                />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <TextField
                    name="starts_at"
                    label="Starts"
                    type="datetime-local"
                    defaultValue={offer?.starts_at_local}
                    error={errors.starts_at}
                    required
                />
                <EndsAtField
                    defaultValue={offer?.ends_at_local}
                    error={errors.ends_at}
                />
            </div>
            <p className="-mt-4 text-sm text-muted-foreground">
                Times are in {timezone}.
            </p>

            <div className="grid gap-4 sm:grid-cols-3">
                <TextField
                    name="uses_per_voucher"
                    label="Uses per voucher"
                    type="number"
                    min="1"
                    max="100"
                    defaultValue={offer?.uses_per_voucher ?? 1}
                    error={errors.uses_per_voucher}
                    required
                />
                <TextField
                    name="voucher_valid_days"
                    label="Voucher lasts (days)"
                    type="number"
                    min="1"
                    defaultValue={offer?.voucher_valid_days ?? ''}
                    error={errors.voucher_valid_days}
                    placeholder="Until the offer ends"
                />
                <VoucherLimitField
                    defaultValue={offer?.voucher_limit}
                    error={errors.voucher_limit}
                />
            </div>
        </>
    );
}

export function DescriptionField({
    defaultValue,
    error,
}: {
    defaultValue?: string | null;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
                id="description"
                name="description"
                rows={3}
                maxLength={2000}
                defaultValue={defaultValue ?? ''}
                aria-invalid={!!error}
                placeholder="What the guest gets and any conditions."
            />
            <InputError message={error} />
        </div>
    );
}

export function EndsAtField({
    defaultValue,
    error,
}: {
    defaultValue?: string;
    error?: string;
}) {
    return (
        <TextField
            name="ends_at"
            label="Ends"
            type="datetime-local"
            defaultValue={defaultValue}
            error={error}
            required
        />
    );
}

export function VoucherLimitField({
    defaultValue,
    error,
}: {
    defaultValue?: number | null;
    error?: string;
}) {
    return (
        <TextField
            name="voucher_limit"
            label="Voucher limit"
            type="number"
            min="1"
            defaultValue={defaultValue ?? ''}
            error={error}
            placeholder="No limit"
        />
    );
}
