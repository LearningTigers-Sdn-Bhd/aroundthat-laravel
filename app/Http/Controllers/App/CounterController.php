<?php

namespace App\Http\Controllers\App;

use App\Actions\Vouchers\CancelRedemption;
use App\Actions\Vouchers\RedeemVoucher;
use App\Data\Forms\CounterCheckData;
use App\Data\Forms\ReasonData;
use App\Data\Forms\RedeemVoucherData;
use App\Data\OutletOptionData;
use App\Data\RedemptionData;
use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Redemption;
use App\Support\Vouchers\DiscountCalculator;
use App\Support\Vouchers\RedemptionRefused;
use App\Support\Vouchers\VoucherEligibility;
use App\Support\Workspace;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Where staff check and redeem guests' vouchers at one of their outlets, and see and cancel today's redemptions.
 * A voucher of another business works here when that business sponsors this outlet on the offer.
 */
class CounterController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function show(Request $request): Response
    {
        $this->workspace->authorize(Ability::Scan);

        $outlets = $this->counterOutlets();
        $rememberKey = 'counter.outlet.'.$this->workspace->membership()->business_id;
        $outlet = $outlets->firstWhere('id', $request->query('outlet'))
            ?? $outlets->firstWhere('id', $request->session()->get($rememberKey))
            ?? ($outlets->count() === 1 ? $outlets->first() : null);

        if ($outlet !== null) {
            $request->session()->put($rememberKey, $outlet->id);
        }

        $showsToday = $outlet !== null && $this->workspace->membership()->can(Ability::ViewTodayActivity);

        return Inertia::render('app/counter', [
            'outlets' => OutletOptionData::collect($outlets),
            'outlet' => $outlet ? OutletOptionData::fromModel($outlet) : null,
            'currency' => config('vouchers.currency'),
            'today' => $showsToday ? $this->today($outlet) : null,
        ]);
    }

    /**
     * Look up a code and, when a bill is given, preview the discount. Nothing is saved. A code that cannot be used
     * answers `eligible: false` with the reason, so the page can tell it from a request that failed.
     *
     * A voucher that does not work at the chosen outlet is checked against the member's other outlets, so a cashier
     * who forgot to switch is not refused: one match is used and named in `outlet`, and several come back as
     * `choose_outlet` for the cashier to pick where the guest is.
     */
    public function check(CounterCheckData $data, VoucherEligibility $eligibility, DiscountCalculator $calculator): JsonResponse
    {
        $outlet = $this->counterOutlet($data->outletId);

        try {
            $voucher = $eligibility->check($data->code, $outlet);
        } catch (RedemptionRefused $refusal) {
            $choices = $refusal->reason === 'outlet_not_permitted'
                ? $eligibility->permittedOutlets($data->code, $this->counterOutlets()->except([$outlet->getKey()]))
                : new EloquentCollection;

            if ($choices->count() > 1) {
                return response()->json([
                    'eligible' => false,
                    'reason' => 'choose_outlet',
                    'message' => __('This voucher works at more than one of your outlets. Choose where the guest is.'),
                    'outlets' => OutletOptionData::collect($choices),
                ]);
            }

            if ($choices->isEmpty()) {
                return $this->refused($refusal);
            }

            $outlet = $choices->first();

            try {
                $voucher = $eligibility->check($data->code, $outlet);
            } catch (RedemptionRefused $refusal) {
                return $this->refused($refusal);
            }
        }

        $offer = $voucher->offer;

        try {
            $amounts = $data->billAmount === null ? null : $calculator->calculate($offer, $data->billAmount, $data->freeItemValue)->toArray();
        } catch (RedemptionRefused $refusal) {
            return $this->refused($refusal);
        }

        return response()->json([
            'eligible' => true,
            'outlet' => OutletOptionData::fromModel($outlet),
            'voucher' => [
                'code_prefix' => $voucher->code_prefix,
                'uses_left' => $voucher->usesLeft(),
                'expires_at' => $voucher->expires_at->min($offer->ends_at)->toIso8601String(),
            ],
            'offer' => [
                'name' => $offer->name,
                'description' => $offer->description,
                'business_name' => $offer->business->name,
                'discount_type' => $offer->discount_type,
                'discount_value' => $offer->discount_value,
                'max_discount_amount' => $offer->max_discount_amount,
                'min_spend_amount' => $offer->min_spend_amount,
                'free_item' => $offer->free_item,
                'currency' => config('vouchers.currency'),
            ],
            'amounts' => $amounts,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function redeem(Request $request, RedeemVoucherData $data, RedeemVoucher $redeemVoucher): RedirectResponse
    {
        $outlet = $this->counterOutlet($data->outletId);

        try {
            $redemption = $redeemVoucher->handle($request->user(), $outlet, $data->code, $data->billAmount, $data->freeItemValue, $data->idempotencyKey);
        } catch (RedemptionRefused $refusal) {
            throw ValidationException::withMessages(['code' => $refusal->getMessage()]);
        }

        $redemption->load(['voucher.offer', 'user', 'cancelledBy']);

        Inertia::flash('redemption', RedemptionData::fromModel($redemption)->toArray());

        return to_route('counter.show');
    }

    /**
     * @throws ValidationException
     */
    public function cancel(Request $request, Redemption $redemption, ReasonData $data, CancelRedemption $cancelRedemption): RedirectResponse
    {
        $this->counterOutlet($redemption->outlet_id);

        $cancelRedemption->handle($redemption, $request->user(), $data->reason);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Redemption cancelled. The voucher has the use back.')]);

        return to_route('counter.show', ['outlet' => $redemption->outlet_id]);
    }

    /**
     * The outlets the member can scan at: theirs, trading, by name.
     *
     * @return EloquentCollection<int, Outlet>
     */
    protected function counterOutlets(): EloquentCollection
    {
        return $this->workspace->membership()->accessibleOutlets()->operational()->orderBy('name')->get();
    }

    protected function refused(RedemptionRefused $refusal): JsonResponse
    {
        return response()->json(['eligible' => false, 'reason' => $refusal->reason, 'message' => $refusal->getMessage()]);
    }

    /**
     * The outlet the member scans at: one of theirs, and trading.
     */
    protected function counterOutlet(string $outletId): Outlet
    {
        $this->workspace->authorize(Ability::Scan);

        $outlet = Outlet::query()->whereKey($outletId)->operational()->first();

        abort_unless($outlet !== null && $this->workspace->membership()->canAccessOutlet($outlet), 404);

        return $outlet;
    }

    /**
     * The outlet's redemptions since midnight in its time zone: the latest 10 and how many there were.
     *
     * @return array{redemptions: list<RedemptionData>, total: int}
     */
    protected function today(Outlet $outlet): array
    {
        $query = Redemption::query()
            ->where('outlet_id', $outlet->id)
            ->where('redeemed_at', '>=', now($outlet->timezone)->startOfDay());

        return [
            'redemptions' => array_values((clone $query)->with(['voucher.offer', 'user', 'cancelledBy'])->latest('redeemed_at')->limit(10)->get()
                ->map(fn (Redemption $redemption): RedemptionData => RedemptionData::fromModel($redemption))
                ->all()),
            'total' => $query->count(),
        ];
    }
}
