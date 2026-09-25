/** What a voucher's QR code holds before the code, so the scanner can tell it from other QR codes. */
export const QR_PREFIX = 'V1:';

/** Ten characters from Crockford's alphabet, which has no I, L, O or U. Mirrors `App\Support\Vouchers\VoucherCode`. */
export const codePattern = /^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{10}$/;

/**
 * What a cashier typed, in capitals, without spaces or dashes, cut to 10 characters.
 */
export function normalizeCode(value: string): string {
    return value.toUpperCase().replace(/[ -]/g, '').slice(0, 10);
}

/**
 * The code inside a scanned QR value, or null when the value is not a voucher.
 */
export function codeFromQr(value: string): string | null {
    if (!value.toUpperCase().startsWith(QR_PREFIX)) {
        return null;
    }

    return normalizeCode(value.slice(QR_PREFIX.length));
}
