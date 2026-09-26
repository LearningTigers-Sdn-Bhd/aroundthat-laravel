<?php

namespace App\Support\Vouchers;

/**
 * The code a guest shows at the counter: 10 characters from Crockford's alphabet (no I, L, O or U), so it reads
 * aloud and types without mix-ups. Only its HMAC is looked up; the code itself is stored encrypted for a later reveal.
 */
class VoucherCode
{
    public const string ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public const int LENGTH = 10;

    /** What a voucher's QR code holds before the code, so a scanner can tell it from other QR codes. */
    public const string QR_PREFIX = 'V1:';

    public static function generate(): string
    {
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }

    /**
     * What a cashier typed or scanned, without the QR prefix, spaces or dashes, in capitals.
     */
    public static function normalize(string $input): string
    {
        $code = strtoupper(trim($input));

        if (str_starts_with($code, self::QR_PREFIX)) {
            $code = substr($code, strlen(self::QR_PREFIX));
        }

        return str_replace([' ', '-'], '', $code);
    }

    /**
     * Whether a normalized code has the right length and characters.
     */
    public static function isWellFormed(string $code): bool
    {
        return strlen($code) === self::LENGTH && strspn($code, self::ALPHABET) === self::LENGTH;
    }

    public static function hash(string $code): string
    {
        $key = hash_hmac('sha256', 'voucher-code-v1', (string) config('app.key'), true);

        return hash_hmac('sha256', $code, $key);
    }

    public static function prefix(string $code): string
    {
        return substr($code, 0, 4);
    }

    public static function qrValue(string $code): string
    {
        return self::QR_PREFIX.$code;
    }

    /**
     * "ABCDE12345" → "ABCDE-12345", easier to read aloud.
     */
    public static function display(string $code): string
    {
        return substr($code, 0, 5).'-'.substr($code, 5);
    }
}
