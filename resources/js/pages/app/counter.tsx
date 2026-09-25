import { Head, router, useHttp, usePage } from '@inertiajs/react';
import { CheckCircle2, ScanLine, Store } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Notice from '@/components/notice';
import ReasonDialog from '@/components/reason-dialog';
import StatusBadge from '@/components/status-badge';
import TextField from '@/components/text-field';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatDateTime } from '@/lib/format';
import { discountSummary, formatMoney } from '@/lib/offers';
import { cancel, check, redeem, show } from '@/routes/counter';

type Props = {
    outlets: App.Data.OutletOptionData[];
    outlet: App.Data.OutletOptionData | null;
    currency: string;
    today: { redemptions: App.Data.RedemptionData[]; total: number } | null;
};

type CheckForm = {
    outlet_id: string;
    code: string;
    bill_amount: string;
    free_item_value: string;
};

type Amounts = {
    bill_amount: string;
    discount_amount: string;
    net_amount: string;
    capped: boolean;
};

type CheckResponse =
    | { eligible: false; reason: string; message: string }
    | {
          eligible: true;
          voucher: {
              code_prefix: string;
              uses_left: number;
              expires_at: string;
          };
          offer: Pick<
              App.Data.OfferData,
              | 'name'
              | 'description'
              | 'discount_type'
              | 'discount_value'
              | 'max_discount_amount'
              | 'min_spend_amount'
              | 'free_item'
              | 'currency'
          > & { business_name: string };
          amounts: Amounts | null;
      };

export default function Counter({ outlets, outlet, currency, today }: Props) {
    if (outlets.length === 0) {
        return (
            <>
                <Head title="Counter" />
                <div className="p-4">
                    <Empty className="border">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Store />
                            </EmptyMedia>
                            <EmptyTitle>No outlet to work at</EmptyTitle>
                            <EmptyDescription>
                                You can redeem vouchers once you work at an
                                approved outlet that is trading.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Counter" />

            <div className="flex max-w-3xl flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Counter"
                        description="Check a guest's voucher, enter the bill and redeem it."
                    />
                    <OutletPicker outlets={outlets} outlet={outlet} />
                </div>

                {outlet ? (
                    <>
                        <Redeem key={outlet.id} outlet={outlet} />
                        {today && (
                            <Today
                                today={today}
                                currency={currency}
                                outlet={outlet}
                            />
                        )}
                    </>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Choose the outlet you are working at.
                    </p>
                )}
            </div>
        </>
    );
}

function OutletPicker({
    outlets,
    outlet,
}: {
    outlets: App.Data.OutletOptionData[];
    outlet: App.Data.OutletOptionData | null;
}) {
    if (outlets.length === 1) {
        return (
            <p className="text-sm text-muted-foreground">
                At <span className="font-medium">{outlet?.name}</span>
            </p>
        );
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor="outlet">Outlet</Label>
            <Select
                items={[
                    { value: null, label: 'Choose an outlet' },
                    ...outlets.map((option) => ({
                        value: option.id,
                        label: option.name,
                    })),
                ]}
                value={outlet?.id ?? null}
                onValueChange={(value) => {
                    if (value) {
                        router.get(show({ query: { outlet: value } }));
                    }
                }}
            >
                <SelectTrigger id="outlet" className="w-64">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {outlets.map((option) => (
                        <SelectItem key={option.id} value={option.id}>
                            {option.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

/**
 * Check a code, preview the discount on a bill, then redeem. Each checked voucher gets a fresh idempotency key,
 * so a double submit or a retry cannot use it twice.
 */
function Redeem({ outlet }: { outlet: App.Data.OutletOptionData }) {
    const page = usePage();
    const errors = page.props.errors;
    const flashedRedemption = page.flash.redemption as
        | App.Data.RedemptionData
        | undefined;
    const http = useHttp<CheckForm, CheckResponse>({
        outlet_id: outlet.id,
        code: '',
        bill_amount: '',
        free_item_value: '',
    });
    const [result, setResult] = useState<CheckResponse | null>(null);
    const [idempotencyKey, setIdempotencyKey] = useState('');
    const [redeeming, setRedeeming] = useState(false);
    const [done, setDone] = useState<App.Data.RedemptionData | undefined>();

    useEffect(() => {
        if (flashedRedemption) {
            setDone(flashedRedemption);
        }
    }, [flashedRedemption]);

    const eligible = result?.eligible ? result : null;

    function runCheck(event?: FormEvent) {
        event?.preventDefault();

        void http.post(check.url(), {
            onSuccess: (response) => {
                if (!result?.eligible || !response.eligible) {
                    setIdempotencyKey(crypto.randomUUID());
                }

                setResult(response);
            },
        });
    }

    function nextGuest() {
        http.reset();
        setResult(null);
        setDone(undefined);
    }

    function submitRedemption() {
        router.post(
            redeem.url(),
            {
                outlet_id: outlet.id,
                code: http.data.code,
                bill_amount: http.data.bill_amount,
                free_item_value: http.data.free_item_value || null,
                idempotency_key: idempotencyKey,
            },
            {
                preserveScroll: true,
                onStart: () => setRedeeming(true),
                onFinish: () => setRedeeming(false),
                onSuccess: () => {
                    http.reset();
                    setResult(null);
                },
            },
        );
    }

    if (done) {
        return (
            <section className="space-y-4 rounded-md border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                <div className="flex items-center gap-2 font-medium text-emerald-900 dark:text-emerald-200">
                    <CheckCircle2 className="size-5" />
                    Redeemed: {done.offer_name}
                </div>
                <Amounts
                    amounts={{
                        bill_amount: done.bill_amount,
                        discount_amount: done.discount_amount,
                        net_amount: done.net_amount,
                        capped: false,
                    }}
                    currency={done.currency}
                />
                <Button onClick={nextGuest}>Next guest</Button>
            </section>
        );
    }

    return (
        <section className="space-y-6">
            <form onSubmit={runCheck} className="flex items-end gap-2">
                <div className="grid flex-1 gap-2">
                    <Label htmlFor="code">Voucher code</Label>
                    <input
                        id="code"
                        value={http.data.code}
                        onChange={(event) => {
                            http.setData('code', event.target.value);
                            setResult(null);
                        }}
                        autoComplete="off"
                        autoCapitalize="characters"
                        autoFocus
                        placeholder="ABCDE-12345"
                        aria-invalid={!!(http.errors.code || errors.code)}
                        className="h-11 w-full rounded-md border border-input bg-transparent px-3 font-mono text-lg tracking-widest uppercase shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 aria-invalid:border-destructive"
                    />
                </div>
                <Button
                    type="submit"
                    size="lg"
                    disabled={http.processing || http.data.code === ''}
                >
                    {http.processing ? <Spinner /> : <ScanLine />}
                    Check
                </Button>
            </form>
            <InputError message={http.errors.code ?? errors.code} />

            {result && !result.eligible && (
                <Notice title="Cannot use this voucher">
                    {result.message}
                </Notice>
            )}

            {eligible && (
                <div className="space-y-4 rounded-md border p-4">
                    <div className="space-y-1">
                        <p className="font-medium">{eligible.offer.name}</p>
                        <p className="text-sm text-muted-foreground">
                            {discountSummary(eligible.offer)} ·{' '}
                            {eligible.offer.business_name}
                        </p>
                        {eligible.offer.description && (
                            <p className="text-sm">
                                {eligible.offer.description}
                            </p>
                        )}
                        <p className="text-sm text-muted-foreground">
                            {eligible.voucher.uses_left}{' '}
                            {eligible.voucher.uses_left === 1 ? 'use' : 'uses'}{' '}
                            left · valid until{' '}
                            {formatDate(eligible.voucher.expires_at)}
                        </p>
                    </div>

                    <form
                        onSubmit={runCheck}
                        className="grid gap-4 sm:grid-cols-2"
                    >
                        <TextField
                            name="bill_amount"
                            label={`Bill (${eligible.offer.currency})`}
                            type="number"
                            step="0.01"
                            min="0.01"
                            value={http.data.bill_amount}
                            onChange={(event) =>
                                http.setData('bill_amount', event.target.value)
                            }
                            error={
                                http.errors.bill_amount ?? errors.bill_amount
                            }
                            required
                        />
                        {eligible.offer.discount_type === 'free_item' && (
                            <TextField
                                name="free_item_value"
                                label={`${eligible.offer.free_item} price (${eligible.offer.currency})`}
                                type="number"
                                step="0.01"
                                min="0.01"
                                value={http.data.free_item_value}
                                onChange={(event) =>
                                    http.setData(
                                        'free_item_value',
                                        event.target.value,
                                    )
                                }
                                error={
                                    http.errors.free_item_value ??
                                    errors.free_item_value
                                }
                                required
                            />
                        )}
                        <div className="sm:col-span-2">
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={
                                    http.processing ||
                                    http.data.bill_amount === ''
                                }
                            >
                                Work out discount
                            </Button>
                        </div>
                    </form>

                    {eligible.amounts && (
                        <>
                            <Amounts
                                amounts={eligible.amounts}
                                currency={eligible.offer.currency}
                            />
                            <Button
                                size="lg"
                                onClick={submitRedemption}
                                disabled={redeeming}
                            >
                                {redeeming && <Spinner />}
                                Redeem
                            </Button>
                        </>
                    )}
                </div>
            )}
        </section>
    );
}

function Amounts({
    amounts,
    currency,
}: {
    amounts: Amounts;
    currency: string;
}) {
    return (
        <dl className="grid grid-cols-3 gap-4 text-sm">
            <div>
                <dt className="text-muted-foreground">Bill</dt>
                <dd className="text-base font-medium">
                    {formatMoney(amounts.bill_amount, currency)}
                </dd>
            </div>
            <div>
                <dt className="text-muted-foreground">
                    Discount{amounts.capped && ' (capped)'}
                </dt>
                <dd className="text-base font-medium">
                    − {formatMoney(amounts.discount_amount, currency)}
                </dd>
            </div>
            <div>
                <dt className="text-muted-foreground">Guest pays</dt>
                <dd className="text-lg font-semibold">
                    {formatMoney(amounts.net_amount, currency)}
                </dd>
            </div>
        </dl>
    );
}

function Today({
    today,
    currency,
    outlet,
}: {
    today: { redemptions: App.Data.RedemptionData[]; total: number };
    currency: string;
    outlet: App.Data.OutletOptionData;
}) {
    return (
        <section className="space-y-3">
            <Heading
                variant="small"
                title="Today"
                description={`${today.total} ${today.total === 1 ? 'redemption' : 'redemptions'} at ${outlet.name} today${today.total > 10 ? ', the latest 10 shown' : ''}.`}
            />
            {today.redemptions.length > 0 && (
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Time</TableHead>
                                <TableHead>Offer</TableHead>
                                <TableHead>Bill</TableHead>
                                <TableHead>Discount</TableHead>
                                <TableHead>By</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {today.redemptions.map((redemption) => (
                                <TableRow key={redemption.id}>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDateTime(redemption.redeemed_at)}
                                    </TableCell>
                                    <TableCell>
                                        {redemption.offer_name}
                                        <span className="ml-2 font-mono text-muted-foreground">
                                            {redemption.code_prefix}…
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        {formatMoney(
                                            redemption.bill_amount,
                                            currency,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {formatMoney(
                                            redemption.discount_amount,
                                            currency,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {redemption.cashier_name ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {redemption.cancelled_at ? (
                                            <StatusBadge status="cancelled" />
                                        ) : (
                                            redemption.can_cancel && (
                                                <ReasonDialog
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                        >
                                                            Cancel
                                                        </Button>
                                                    }
                                                    title="Cancel this redemption?"
                                                    description="The voucher gets the use back. The redemption stays on the list, marked cancelled."
                                                    form={cancel.form(
                                                        redemption.id,
                                                    )}
                                                    submitLabel="Cancel redemption"
                                                    destructive
                                                />
                                            )
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}
        </section>
    );
}

Counter.layout = {
    breadcrumbs: [{ title: 'Counter', href: show() }],
};
