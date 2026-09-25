import { Link } from '@inertiajs/react';
import { ArrowLeft, Keyboard } from 'lucide-react';
import type { FormEvent } from 'react';
import CameraScanner from '@/components/app/counter/camera-scanner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';

/**
 * The start of every redemption: the camera, or the code typed by hand when the camera cannot read it.
 */
export default function ScanStep({
    step,
    busy,
    error,
    typedCode,
    onScannedValue,
    onTypedCode,
    onSubmitTyped,
    onChooseType,
    onBack,
}: {
    step: 'scan' | 'type';
    busy: boolean;
    error?: string;
    typedCode: string;
    onScannedValue: (value: string) => boolean;
    onTypedCode: (value: string) => void;
    onSubmitTyped: (event: FormEvent) => void;
    onChooseType: () => void;
    onBack: () => void;
}) {
    if (step === 'type') {
        return (
            <section className="flex flex-1 flex-col justify-center py-6 text-center">
                <p className="text-sm font-medium tracking-[0.2em] text-primary uppercase">
                    Type voucher
                </p>
                <h2 className="mt-3 text-4xl font-semibold tracking-tight">
                    Enter the 10 characters
                </h2>
                <p className="mt-3 text-muted-foreground">
                    There is no letter I, L, O or U.
                </p>
                <form onSubmit={onSubmitTyped} className="mt-8 space-y-5">
                    <Input
                        autoFocus
                        inputMode="text"
                        autoCapitalize="characters"
                        autoComplete="off"
                        autoCorrect="off"
                        spellCheck={false}
                        enterKeyHint="go"
                        aria-label="Voucher code"
                        value={typedCode}
                        onChange={(event) => onTypedCode(event.target.value)}
                        aria-invalid={!!error}
                        aria-describedby={
                            error ? 'typed-code-error' : undefined
                        }
                        placeholder="ABCDE12345"
                        className="h-20 rounded-3xl px-5 text-center font-mono text-2xl font-semibold tracking-[0.22em] md:text-2xl"
                    />
                    {error && (
                        <p
                            id="typed-code-error"
                            role="alert"
                            className="text-sm text-destructive"
                        >
                            {error}
                        </p>
                    )}
                    <div className="grid grid-cols-2 gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            onClick={onBack}
                            className="h-14 text-base"
                        >
                            <ArrowLeft data-icon="inline-start" />
                            Camera
                        </Button>
                        <Button
                            type="submit"
                            size="lg"
                            disabled={busy}
                            className="h-14 text-base"
                        >
                            {busy ? <Spinner /> : 'Continue'}
                        </Button>
                    </div>
                </form>
            </section>
        );
    }

    return (
        <section className="flex flex-1 flex-col gap-4">
            <h2 className="sr-only">Point the camera at the voucher</h2>
            <CameraScanner active={!busy} onCode={onScannedValue} />
            {error && (
                <div
                    role="alert"
                    className="rounded-2xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive"
                >
                    {error}
                </div>
            )}
            <div className="grid grid-cols-2 gap-3">
                <Button
                    variant="outline"
                    size="lg"
                    onClick={onChooseType}
                    disabled={busy}
                    className="h-14 text-base"
                >
                    {busy ? <Spinner /> : <Keyboard data-icon="inline-start" />}
                    Type code
                </Button>
                <Button
                    variant="outline"
                    size="lg"
                    className="h-14 text-base"
                    nativeButton={false}
                    render={<Link href={dashboard()} />}
                >
                    <ArrowLeft data-icon="inline-start" />
                    Back
                </Button>
            </div>
        </section>
    );
}
