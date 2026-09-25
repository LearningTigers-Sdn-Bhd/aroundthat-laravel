import { ArrowLeft } from 'lucide-react';
import type { FormEvent } from 'react';
import type { EligibleCheck } from '@/components/app/counter/types';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
    InputGroupText,
} from '@/components/ui/input-group';
import { Spinner } from '@/components/ui/spinner';
import { formatDate } from '@/lib/format';
import { discountSummary } from '@/lib/offers';

/**
 * The offer the voucher is for, and the bill it is rung against.
 */
export default function BillStep({
    check,
    billAmount,
    freeItemValue,
    busy,
    errors,
    onBillAmount,
    onFreeItemValue,
    onSubmit,
    onBack,
}: {
    check: EligibleCheck;
    billAmount: string;
    freeItemValue: string;
    busy: boolean;
    errors: { bill_amount?: string; free_item_value?: string };
    onBillAmount: (value: string) => void;
    onFreeItemValue: (value: string) => void;
    onSubmit: (event: FormEvent) => void;
    onBack: () => void;
}) {
    const { offer, voucher } = check;

    return (
        <section className="mx-auto flex w-full max-w-lg flex-1 flex-col justify-center py-6">
            <Button
                variant="ghost"
                onClick={onBack}
                className="mb-6 w-fit text-muted-foreground"
            >
                <ArrowLeft data-icon="inline-start" /> Scan another voucher
            </Button>
            <div className="rounded-3xl border border-primary/15 bg-primary/5 p-5">
                <p className="text-sm font-medium text-primary">{offer.name}</p>
                <p className="mt-1 text-xl font-semibold">
                    {discountSummary(offer)}
                </p>
                {offer.description && (
                    <p className="mt-2 text-sm">{offer.description}</p>
                )}
                <p className="mt-2 text-sm text-muted-foreground">
                    {offer.business_name} · {voucher.uses_left}{' '}
                    {voucher.uses_left === 1 ? 'use' : 'uses'} left · valid
                    until {formatDate(voucher.expires_at)}
                </p>
            </div>
            <form onSubmit={onSubmit} className="mt-8 space-y-6">
                <label className="block">
                    <span className="text-sm font-medium text-muted-foreground">
                        Full bill before discount
                    </span>
                    <InputGroup className="mt-2 h-20 rounded-3xl px-2">
                        <InputGroupAddon>
                            <InputGroupText className="text-lg">
                                {offer.currency}
                            </InputGroupText>
                        </InputGroupAddon>
                        <InputGroupInput
                            autoFocus
                            required
                            type="number"
                            step="0.01"
                            min="0.01"
                            inputMode="decimal"
                            enterKeyHint="go"
                            aria-invalid={!!errors.bill_amount}
                            value={billAmount}
                            onChange={(event) =>
                                onBillAmount(event.target.value)
                            }
                            placeholder="0.00"
                            className="text-4xl font-semibold tracking-tight tabular-nums md:text-4xl"
                        />
                    </InputGroup>
                    <InputError message={errors.bill_amount} className="mt-2" />
                </label>
                {offer.discount_type === 'free_item' && (
                    <label className="block">
                        <span className="text-sm font-medium text-muted-foreground">
                            Price of the {offer.free_item}
                        </span>
                        <InputGroup className="mt-2 h-16 rounded-2xl px-2">
                            <InputGroupAddon>
                                <InputGroupText>
                                    {offer.currency}
                                </InputGroupText>
                            </InputGroupAddon>
                            <InputGroupInput
                                required
                                type="number"
                                step="0.01"
                                min="0.01"
                                inputMode="decimal"
                                enterKeyHint="go"
                                aria-invalid={!!errors.free_item_value}
                                value={freeItemValue}
                                onChange={(event) =>
                                    onFreeItemValue(event.target.value)
                                }
                                placeholder="0.00"
                                className="text-2xl font-semibold tabular-nums md:text-2xl"
                            />
                        </InputGroup>
                        <InputError
                            message={errors.free_item_value}
                            className="mt-2"
                        />
                    </label>
                )}
                <Button
                    type="submit"
                    size="lg"
                    disabled={busy || billAmount === ''}
                    className="h-14 w-full text-base"
                >
                    {busy ? <Spinner /> : 'Check discount'}
                </Button>
            </form>
        </section>
    );
}
