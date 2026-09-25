import { Check, Copy } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';

export type ShownVoucher = { id: string; code: string; qr_value: string };

/**
 * A voucher code shown once after it is issued or revealed, to hand to the guest.
 */
export default function VoucherCodeNotice({
    voucher,
}: {
    voucher: ShownVoucher;
}) {
    const [copied, copy] = useClipboard();

    return (
        <div className="space-y-2 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            <p className="font-medium">
                Give the guest this code now. It is not shown again without your
                password.
            </p>
            <div className="flex flex-wrap items-center gap-4">
                {/* White behind the code in both themes, so phone cameras read it. */}
                <div className="rounded-md bg-white p-2">
                    <QRCodeSVG
                        value={voucher.qr_value}
                        level="Q"
                        size={160}
                        title={`QR code for voucher ${voucher.code}`}
                    />
                </div>
                <div className="flex items-center gap-2">
                    <code className="rounded bg-background px-3 py-1.5 font-mono text-lg tracking-widest text-foreground">
                        {voucher.code}
                    </code>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => void copy(voucher.code)}
                    >
                        {copied === voucher.code ? <Check /> : <Copy />}
                        {copied === voucher.code ? 'Copied' : 'Copy'}
                    </Button>
                </div>
            </div>
        </div>
    );
}
