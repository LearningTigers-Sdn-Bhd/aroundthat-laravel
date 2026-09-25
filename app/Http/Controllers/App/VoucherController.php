<?php

namespace App\Http\Controllers\App;

use App\Actions\Vouchers\IssueVoucher;
use App\Actions\Vouchers\VoidVoucher;
use App\Data\Forms\ReasonData;
use App\Data\OfferData;
use App\Data\VoucherData;
use App\Enums\Ability;
use App\Enums\VoucherStatus;
use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use App\Support\ActivityLog\AuditTrail;
use App\Support\Vouchers\VoucherCode;
use App\Support\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * An offer's vouchers: list them, issue one at the counter, reveal a code again, or void one.
 * A new or revealed code is flashed once as `voucher`.
 */
class VoucherController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function index(Request $request, VoucherOffer $offer): Response
    {
        $this->workspace->ensureOwns($offer);
        $this->workspace->authorize(Ability::ManageOffers);

        $offer->load(['business', 'outlets.business']);

        $vouchers = QueryBuilder::for($offer->vouchers(), $request)
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, mixed $value) => $query
                    ->where('code_prefix', substr(VoucherCode::normalize((string) $value), 0, 4))),
                AllowedFilter::callback('status', fn (Builder $query, mixed $value) => match ($value) {
                    'expired' => $query->where('status', VoucherStatus::Active)->where('expires_at', '<=', now()),
                    'active' => $query->where('status', VoucherStatus::Active)->where('expires_at', '>', now()),
                    default => $query->where('status', $value),
                }),
            )
            ->allowedSorts('created_at', 'expires_at')
            ->defaultSort('-created_at')
            ->with('integration')
            ->paginate(25)
            ->withQueryString();

        $vouchers->getCollection()->each->setRelation('offer', $offer);

        return Inertia::render('app/offers/vouchers', [
            'offer' => OfferData::fromModel($offer),
            'vouchers' => VoucherData::collect($vouchers, PaginatedDataCollection::class),
            'can' => [
                'issue' => $request->user()->can('issueVouchers', $offer),
                'void' => $request->user()->can('voidVouchers', $offer),
            ],
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(VoucherOffer $offer, IssueVoucher $issueVoucher): RedirectResponse
    {
        $this->workspace->ensureOwns($offer);
        Gate::authorize('issueVouchers', $offer);

        [$voucher, $code] = $issueVoucher->handle($offer);

        return $this->showCode($voucher, $code, __('Voucher issued. Give the guest the code now; it is not shown again.'));
    }

    /**
     * Show a code again after the member confirms their password. Each reveal is logged.
     */
    public function reveal(Request $request, VoucherOffer $offer, Voucher $voucher, AuditTrail $audit): RedirectResponse
    {
        $this->workspace->ensureOwns($offer);
        Gate::authorize('issueVouchers', $offer);

        $request->validate(['password' => ['required', 'current_password']]);

        $audit->record($voucher, 'code_revealed');

        return $this->showCode($voucher, $voucher->code, __('Code revealed.'));
    }

    /**
     * @throws ValidationException
     */
    public function void(VoucherOffer $offer, Voucher $voucher, ReasonData $data, VoidVoucher $voidVoucher): RedirectResponse
    {
        $this->workspace->ensureOwns($offer);
        Gate::authorize('voidVouchers', $offer);

        $voidVoucher->handle($voucher, $data->reason);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Voucher voided. It can no longer be used.')]);

        return back();
    }

    protected function showCode(Voucher $voucher, string $code, string $message): RedirectResponse
    {
        Inertia::flash('voucher', [
            'id' => $voucher->id,
            'code' => VoucherCode::display($code),
            'qr_value' => VoucherCode::qrValue($code),
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
