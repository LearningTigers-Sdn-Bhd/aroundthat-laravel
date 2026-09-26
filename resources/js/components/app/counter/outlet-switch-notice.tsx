import { MapPin } from 'lucide-react';

/**
 * Says the voucher is being redeemed at another of the cashier's outlets than the one picked in the header.
 */
export default function OutletSwitchNotice({
    outletName,
}: {
    outletName: string;
}) {
    return (
        <p className="flex items-center gap-2 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">
            <MapPin className="size-4 shrink-0" />
            <span>
                This voucher is for <strong>{outletName}</strong>. It will be
                redeemed there.
            </span>
        </p>
    );
}
