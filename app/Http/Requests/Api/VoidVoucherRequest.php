<?php

namespace App\Http\Requests\Api;

use App\Enums\VoidReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A partner cancels a voucher it claimed.
 */
class VoidVoucherRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason_code' => ['required', Rule::enum(VoidReason::class)],
        ];
    }
}
