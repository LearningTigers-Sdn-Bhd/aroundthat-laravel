import { ArrowLeft, Keyboard } from 'lucide-react';
import type { FormEvent } from 'react';
import CameraScanner from '@/components/app/counter/camera-scanner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

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
            <section className="mx-auto flex w-full max-w-lg flex-1 flex-col justify-center py-6">
                <Button
                    variant="ghost"
                    onClick={onBack}
                    className="mb-8 w-fit text-muted-foreground"
                >
                    <ArrowLeft data-icon="inline-start" /> Back to camera
                </Button>
                <p className="text-sm font-medium tracking-[0.2em] text-primary uppercase">
                    Type voucher
                </p>
                <h2 className="mt-3 text-4xl font-semibold tracking-tight sm:text-5xl">
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
                    <Button
                        type="submit"
                        size="lg"
                        disabled={busy}
                        className="h-14 w-full text-base"
                    >
                        {busy ? <Spinner /> : 'Continue'}
                    </Button>
                </form>
            </section>
        );
    }

    return (
        <section className="flex flex-1 flex-col">
            <div className="mb-5">
                <p className="text-sm font-medium tracking-[0.2em] text-primary uppercase">
                    Ready for the next guest
                </p>
                <h2 className="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                    Point at the voucher
                </h2>
            </div>
            <CameraScanner active={!busy} onCode={onScannedValue} />
            {error && (
                <div
                    role="alert"
                    className="mt-4 rounded-2xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive"
                >
                    {error}
                </div>
            )}
            <Button
                variant="outline"
                size="lg"
                onClick={onChooseType}
                disabled={busy}
                className="mt-5 h-14 w-full text-base"
            >
                {busy ? <Spinner /> : <Keyboard data-icon="inline-start" />}
                Type the code instead
            </Button>
        </section>
    );
}
