<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Vouchers\ClaimVoucher;
use App\Actions\Vouchers\VoidVoucher;
use App\Enums\VoidReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClaimVoucherRequest;
use App\Http\Requests\Api\VoidVoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Integration;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use App\Support\Api\ApiErrors;
use App\Support\Vouchers\VoucherCode;
use App\Support\Vouchers\VoucherLimitReached;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @tags Vouchers
 */
class VoucherController extends Controller
{
    /**
     * Claim a voucher for a guest
     *
     * Issues a voucher of a published offer and returns its code once, with `claim_status` `created` (201).
     * Give the guest the code or show `qr_value` as a QR code; cashiers scan either at the counter.
     *
     * A guest holds at most one live voucher of an offer: while it is active, a new claim returns it with
     * `claim_status` `existing_active` and no code. Resending the same `idempotency_key` with the same body returns
     * the first voucher and its code with `claim_status` `idempotent_replay`; with a different body it fails with 409
     * `idempotency_conflict`. A fully claimed offer fails with 409 `offer_limit_reached`.
     *
     * @throws ValidationException
     */
    public function store(ClaimVoucherRequest $request, string $offer, ClaimVoucher $claimVoucher): JsonResponse
    {
        $integration = $this->integration();

        $voucherOffer = VoucherOffer::query()
            ->published()
            ->whereHas('outlets', fn (Builder $outlets) => $outlets->public())
            ->whereKey($offer)
            ->firstOrFail();

        $outlet = null;

        if ($request->filled('outlet')) {
            $outlet = $voucherOffer->outlets()->public()->where('slug', $request->validated('outlet'))->first()
                ?? throw ValidationException::withMessages(['outlet' => __('This outlet is not public or does not take this offer.')]);
        }

        try {
            $result = $claimVoucher->handle($integration, $voucherOffer, $request->validated('guest_ref'), $request->validated('idempotency_key'), $outlet);
        } catch (VoucherLimitReached) {
            return ApiErrors::response(409, 'offer_limit_reached', __('Every voucher of this offer has been claimed.'));
        }

        if ($result['voucher'] === null) {
            return ApiErrors::response(409, 'idempotency_conflict', __('This idempotency key was used for a different claim.'), '/idempotency_key');
        }

        $voucher = $result['voucher']->setRelation('offer', $voucherOffer);

        return response()->json([
            'data' => [
                /** `created`, `existing_active` or `idempotent_replay`. */
                'claim_status' => $result['status'],
                'voucher' => new VoucherResource($voucher),
                /** The code to give the guest, shown as `ABCDE-12345`. Only with `created` and `idempotent_replay`. */
                'code' => $result['code'] === null ? null : VoucherCode::display($result['code']),
                /** What a QR code for the guest should hold. Only with `created` and `idempotent_replay`. */
                'qr_value' => $result['code'] === null ? null : VoucherCode::qrValue($result['code']),
            ],
        ], $result['status'] === 'created' ? 201 : 200);
    }

    /**
     * Show a voucher
     *
     * Only vouchers your integration claimed. The code is never returned again.
     */
    public function show(string $voucher): VoucherResource
    {
        return new VoucherResource($this->ownVoucher($voucher));
    }

    /**
     * Void a voucher
     *
     * Cancels an active voucher your integration claimed, for good. A voucher that is used, void or expired fails
     * with 409 `voucher_not_active`.
     */
    public function void(VoidVoucherRequest $request, string $voucher, VoidVoucher $voidVoucher): VoucherResource|JsonResponse
    {
        $ownVoucher = $this->ownVoucher($voucher);

        try {
            $voidVoucher->handle($ownVoucher, VoidReason::from($request->validated('reason_code')));
        } catch (ValidationException) {
            return ApiErrors::response(409, 'voucher_not_active', __('Only an active voucher that has not expired can be voided.'));
        }

        return new VoucherResource($ownVoucher->refresh()->load('offer'));
    }

    protected function integration(): Integration
    {
        $integration = Auth::guard('sanctum')->user();
        abort_unless($integration instanceof Integration, 401);

        return $integration;
    }

    protected function ownVoucher(string $id): Voucher
    {
        return Voucher::query()
            ->where('integration_id', $this->integration()->getKey())
            ->whereKey($id)
            ->with('offer')
            ->firstOrFail();
    }
}
