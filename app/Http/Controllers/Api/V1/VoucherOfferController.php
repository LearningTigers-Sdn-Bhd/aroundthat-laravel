<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoucherOfferResource;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Vouchers
 */
class VoucherOfferController extends Controller
{
    public const int DEFAULT_PER_PAGE = 25;

    public const int MAX_PER_PAGE = 100;

    /**
     * List an outlet's voucher offers
     *
     * Offers a guest can claim now and use at the outlet: running, inside their dates and not fully claimed.
     * Cursor-paginated, soonest ending first.
     */
    #[QueryParameter('per_page', description: 'Offers per page, 1 to 100.', type: 'integer', default: self::DEFAULT_PER_PAGE)]
    #[QueryParameter('cursor', description: 'The `meta.next_cursor` of the previous page.', type: 'string')]
    public function index(Request $request, string $slug): AnonymousResourceCollection
    {
        $validated = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE]]);

        $outlet = Outlet::query()->public()->where('slug', $slug)->firstOrFail();

        $offers = VoucherOffer::query()
            ->published()
            ->underVoucherLimit()
            ->whereHas('outlets', fn (Builder $outlets) => $outlets->whereKey($outlet->getKey()))
            ->with(['business', 'outlets' => fn ($outlets) => $outlets->public()->orderBy('name')])
            ->orderBy('ends_at')
            ->orderBy('id')
            ->cursorPaginate($validated['per_page'] ?? self::DEFAULT_PER_PAGE)
            ->withQueryString();

        return VoucherOfferResource::collection($offers);
    }
}
