import { Head, router, useHttp, usePage } from '@inertiajs/react';
import { Store } from 'lucide-react';
import { type FormEvent, useCallback, useEffect, useState } from 'react';
import ActivityBar from '@/components/app/counter/activity-bar';
import BillStep from '@/components/app/counter/bill-step';
import ConfirmStep from '@/components/app/counter/confirm-step';
import CounterHeader from '@/components/app/counter/counter-header';
import DoneStep from '@/components/app/counter/done-step';
import HistoryPanel from '@/components/app/counter/history-panel';
import OutletStep from '@/components/app/counter/outlet-step';
import RejectedStep from '@/components/app/counter/rejected-step';
import ScanStep from '@/components/app/counter/scan-step';
import type {
    Amounts,
    CheckResponse,
    EligibleCheck,
} from '@/components/app/counter/types';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useOnline } from '@/hooks/use-online';
import { codeFromQr, codePattern, normalizeCode } from '@/lib/voucher-code';
import { check, redeem, show } from '@/routes/counter';

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

export default function Counter({ outlets, outlet, today }: Props) {
    const workspace = usePage().props.workspace;
    const online = useOnline();
    const [historyOpen, setHistoryOpen] = useState(false);

    return (
        <>
            <Head title="Counter" />

            <main className="mx-auto flex min-h-dvh w-full max-w-3xl flex-col">
                <CounterHeader
                    businessName={workspace?.business_name ?? 'Counter'}
                    online={online}
                    outletPicker={
                        outlets.length > 0 && (
                            <OutletPicker outlets={outlets} outlet={outlet} />
                        )
                    }
                />

                <div className="flex flex-1 flex-col px-4 pb-32 sm:px-8">
                    {outlets.length === 0 ? (
                        <CounterNotice
                            title="No outlet to work at"
                            description="You can redeem vouchers once you work at an approved outlet that is trading."
                        />
                    ) : outlet ? (
                        <Redeem
                            key={outlet.id}
                            outlet={outlet}
                            online={online}
                        />
                    ) : (
                        <CounterNotice
                            title="Choose your outlet"
                            description="Pick the outlet you are working at to start scanning."
                        />
                    )}
                </div>

                {outlet && today && (
                    <ActivityBar
                        count={today.total}
                        onOpen={() => {
                            setHistoryOpen(true);
                            router.reload({ only: ['today'] });
                        }}
                    />
                )}
            </main>

            {outlet && today && (
                <HistoryPanel
                    open={historyOpen}
                    today={today}
                    outletName={outlet.name}
                    onClose={() => setHistoryOpen(false)}
                />
            )}
        </>
    );
}

function CounterNotice({
    title,
    description,
}: {
    title: string;
    description: string;
}) {
    return (
        <section className="mt-10 rounded-3xl border bg-card p-8 text-center shadow-sm">
            <Store className="mx-auto size-10 text-muted-foreground" />
            <h1 className="mt-4 text-2xl font-semibold">{title}</h1>
            <p className="mx-auto mt-2 max-w-md text-muted-foreground">
                {description}
            </p>
        </section>
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
                At{' '}
                <span className="font-medium text-foreground">
                    {outlet?.name}
                </span>
            </p>
        );
    }

    return (
        <div>
            <Label htmlFor="outlet" className="sr-only">
                Outlet
            </Label>
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
                <SelectTrigger id="outlet" className="w-44 sm:w-56">
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

type Step =
    | { step: 'scan'; error: string }
    | { step: 'type'; error: string }
    | {
          step: 'outlet';
          outlets: App.Data.OutletOptionData[];
          message: string;
      }
    | { step: 'bill'; check: EligibleCheck }
    | {
          step: 'confirm';
          check: EligibleCheck;
          amounts: Amounts;
          /** Minted when the bill is priced, so retrying the accept cannot use the voucher twice. */
          idempotencyKey: string;
      }
    | { step: 'done'; redemption: App.Data.RedemptionData }
    | {
          step: 'rejected';
          message: string;
          /** The priced bill to go back to when the accept is what failed. */
          resume: Extract<Step, { step: 'confirm' }> | null;
      };

/** The result screen returns to the camera on its own after this long. */
const DONE_TIMEOUT = 10_000;

const REFUSED = 'This voucher could not be checked';

/**
 * One voucher from the camera to an accepted redemption: scan or type the code, enter the bill, show the guest the
 * price, accept.
 */
function Redeem({
    outlet,
    online,
}: {
    outlet: App.Data.OutletOptionData;
    online: boolean;
}) {
    const page = usePage();
    const flashedRedemption = page.flash.redemption as
        | App.Data.RedemptionData
        | undefined;
    const http = useHttp<CheckForm, CheckResponse>({
        outlet_id: outlet.id,
        code: '',
        bill_amount: '',
        free_item_value: '',
    });
    const [step, setStep] = useState<Step>({ step: 'scan', error: '' });
    const [redeeming, setRedeeming] = useState(false);
    /** Form data a check waits for: `setData` lands on the next render, so the check runs once it has. */
    const [pendingCheck, setPendingCheck] = useState<Partial<CheckForm> | null>(
        null,
    );
    const [doneHeld, setDoneHeld] = useState(false);

    const busy = http.processing || redeeming;
    const { reset: resetForm, clearErrors } = http;

    const nextGuest = useCallback(() => {
        resetForm();
        clearErrors();
        setDoneHeld(false);
        setStep({ step: 'scan', error: '' });
    }, [resetForm, clearErrors]);

    useEffect(() => {
        if (flashedRedemption) {
            setDoneHeld(false);
            setStep({ step: 'done', redemption: flashedRedemption });
        }
    }, [flashedRedemption]);

    useEffect(() => {
        if (step.step !== 'done' || doneHeld) {
            return;
        }

        const timer = window.setTimeout(nextGuest, DONE_TIMEOUT);

        return () => window.clearTimeout(timer);
    }, [doneHeld, nextGuest, step.step]);

    useEffect(() => {
        if (
            pendingCheck !== null &&
            Object.entries(pendingCheck).every(
                ([key, value]) => http.data[key as keyof CheckForm] === value,
            )
        ) {
            setPendingCheck(null);
            runCheck();
        }
    });

    function acceptScan(value: string): boolean {
        const code = codeFromQr(value);

        if (code === null) {
            setStep({ step: 'scan', error: 'This is not a voucher' });

            return false;
        }

        if (!codePattern.test(code)) {
            setStep({ step: 'scan', error: 'This voucher code is not valid' });

            return false;
        }

        http.setData('code', code);
        setPendingCheck({ code });

        return true;
    }

    function submitTyped(event: FormEvent) {
        event.preventDefault();

        if (!codePattern.test(http.data.code)) {
            setStep({
                step: 'type',
                error: 'Enter all 10 characters. The letters I, L, O and U are not used',
            });

            return;
        }

        runCheck();
    }

    function runCheck(event?: FormEvent) {
        event?.preventDefault();
        const from = step.step;

        void http.post(check.url(), {
            onSuccess: (response) => {
                if (!response.eligible && 'outlets' in response) {
                    setStep({
                        step: 'outlet',
                        outlets: response.outlets,
                        message: response.message,
                    });
                } else if (!response.eligible) {
                    setStep({
                        step: 'rejected',
                        message: response.message,
                        resume: null,
                    });
                } else if (response.outlet.id !== http.data.outlet_id) {
                    // The voucher is for another of the cashier's outlets: check and redeem there from now on.
                    http.setData('outlet_id', response.outlet.id);
                    setStep({ step: 'bill', check: response });
                } else if (response.amounts) {
                    setStep({
                        step: 'confirm',
                        check: response,
                        amounts: response.amounts,
                        idempotencyKey: crypto.randomUUID(),
                    });
                } else {
                    setStep({ step: 'bill', check: response });
                }
            },
            onError: (errors) => {
                // Bill errors show under their fields; a code the server would not read ends the attempt.
                if (from !== 'bill') {
                    setStep({
                        step: 'rejected',
                        message: Object.values(errors)[0] ?? REFUSED,
                        resume: null,
                    });
                }
            },
        });
    }

    function accept() {
        if (step.step !== 'confirm' || !online) {
            return;
        }

        const priced = step;

        router.post(
            redeem.url(),
            {
                outlet_id: http.data.outlet_id,
                code: http.data.code,
                bill_amount: http.data.bill_amount,
                free_item_value: http.data.free_item_value || null,
                idempotency_key: priced.idempotencyKey,
            },
            {
                preserveScroll: true,
                onStart: () => setRedeeming(true),
                onFinish: () => setRedeeming(false),
                onSuccess: () => http.reset(),
                onError: (errors) =>
                    setStep({
                        step: 'rejected',
                        message: Object.values(errors)[0] ?? REFUSED,
                        resume: priced,
                    }),
            },
        );
    }

    switch (step.step) {
        case 'scan':
        case 'type':
            return (
                <ScanStep
                    step={step.step}
                    busy={busy}
                    error={step.error}
                    typedCode={http.data.code}
                    onScannedValue={acceptScan}
                    onTypedCode={(value) => {
                        http.setData('code', normalizeCode(value));
                        setStep({ step: 'type', error: '' });
                    }}
                    onSubmitTyped={submitTyped}
                    onChooseType={() => {
                        http.setData('code', '');
                        setStep({ step: 'type', error: '' });
                    }}
                    onBack={() => setStep({ step: 'scan', error: '' })}
                />
            );

        case 'outlet':
            return (
                <OutletStep
                    outlets={step.outlets}
                    message={step.message}
                    onChoose={(choice) => {
                        http.setData('outlet_id', choice.id);
                        setPendingCheck({ outlet_id: choice.id });
                    }}
                    onBack={nextGuest}
                />
            );

        case 'bill':
            return (
                <BillStep
                    check={step.check}
                    switchedOutlet={
                        step.check.outlet.id !== outlet.id
                            ? step.check.outlet
                            : null
                    }
                    billAmount={http.data.bill_amount}
                    freeItemValue={http.data.free_item_value}
                    busy={busy}
                    errors={http.errors}
                    onBillAmount={(value) => http.setData('bill_amount', value)}
                    onFreeItemValue={(value) =>
                        http.setData('free_item_value', value)
                    }
                    onSubmit={runCheck}
                    onBack={nextGuest}
                />
            );

        case 'confirm':
            return (
                <ConfirmStep
                    offer={step.check.offer}
                    switchedOutlet={
                        step.check.outlet.id !== outlet.id
                            ? step.check.outlet
                            : null
                    }
                    amounts={step.amounts}
                    busy={busy}
                    online={online}
                    onAccept={accept}
                    onBack={() => setStep({ step: 'bill', check: step.check })}
                />
            );

        case 'done':
            return (
                <DoneStep
                    redemption={step.redemption}
                    held={doneHeld}
                    onNext={nextGuest}
                    onHold={() => setDoneHeld(true)}
                />
            );

        case 'rejected':
            return (
                <RejectedStep
                    message={step.message}
                    onRetry={() =>
                        step.resume ? setStep(step.resume) : nextGuest()
                    }
                />
            );
    }
}
