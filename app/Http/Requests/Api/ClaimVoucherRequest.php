<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A partner's claim of one voucher for one of its guests.
 */
class ClaimVoucherRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            /** Your own reference for the guest, 1 to 255 characters, without spaces at either end. It is stored only as a one-way hash. */
            'guest_ref' => ['required', 'string', 'max:255'],
            /** A new key for each claim, 1 to 255 characters. Resending a key returns the same voucher and code. */
            'idempotency_key' => ['required', 'string', 'max:255'],
            /** The slug of the outlet you showed the guest, if any. It records where the claim came from and never limits where the voucher can be used. */
            'outlet' => ['nullable', 'string', 'max:255'],
        ];
    }
}
