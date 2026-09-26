import { Check, Copy, Download } from 'lucide-react';
import { QRCodeCanvas } from 'qrcode.react';
import { useRef } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useClipboard } from '@/hooks/use-clipboard';

export type ShownVoucher = { id: string; code: string; qr_value: string };

/**
 * A voucher code shown once after it is issued or revealed, to hand to the guest: the QR code to scan or download,
 * and the code to read out or copy.
 */
export default function VoucherCodeDialog({
    voucher,
    onClose,
}: {
    voucher: ShownVoucher | undefined;
    onClose: () => void;
}) {
    const [copied, copy] = useClipboard();
    const canvas = useRef<HTMLCanvasElement>(null);

    const downloadQrCode = () => {
        if (!voucher || !canvas.current) {
            return;
        }

        const link = document.createElement('a');
        link.href = canvas.current.toDataURL('image/png');
        link.download = `voucher-${voucher.code}.png`;
        link.click();
    };

    return (
        <Dialog
            open={voucher !== undefined}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Voucher code</DialogTitle>
                    <DialogDescription>
                        Give the guest this code now. It is not shown again
                        without your password.
                    </DialogDescription>
                </DialogHeader>

                {voucher && (
                    <div className="flex flex-col items-center gap-4">
                        {/* A white margin around the code in both themes, so phone cameras read it. The canvas is drawn at 512px and shown smaller, so the download stays sharp. */}
                        <QRCodeCanvas
                            ref={canvas}
                            value={voucher.qr_value}
                            level="Q"
                            size={512}
                            marginSize={4}
                            title={`QR code for voucher ${voucher.code}`}
                            style={{ width: 224, height: 224 }}
                            className="rounded-md"
                        />
                        <div className="flex items-center gap-2">
                            <code className="rounded bg-muted px-3 py-1.5 font-mono text-lg tracking-widest">
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
                )}

                <DialogFooter className="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={downloadQrCode}
                    >
                        <Download />
                        Download QR code
                    </Button>
                    <Button type="button" onClick={onClose}>
                        Done
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
