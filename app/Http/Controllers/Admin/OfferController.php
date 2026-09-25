<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\ActivityData;
use App\Data\Admin\HostOutletOptionData;
use App\Data\Admin\OfferData;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use App\Support\QueryFilters\SearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Every business's voucher offers, so admins can take one down or add sponsored outlets.
 */
class OfferController extends Controller
{
    public function index(Request $request): Response
    {
        $offers = QueryBuilder::for(VoucherOffer::class, $request)
            ->allowedFilters(
                SearchFilter::on(['name']),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('visibility', fn (Builder $query, mixed $value) => $value === 'hidden'
                    ? $query->whereNotNull('hidden_at')
                    : $query->whereNull('hidden_at')),
                AllowedFilter::callback('sponsored', fn (Builder $query) => $query->whereHas(
                    'outlets',
                    fn (Builder $outlets) => $outlets->whereColumn('outlets.business_id', '<>', 'voucher_offers.business_id'),
                )),
            )
            ->allowedSorts('name', 'created_at', 'ends_at')
            ->defaultSort('-created_at')
            ->with(['business', 'outlets.business'])
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/offers/index', [
            'offers' => OfferData::collect($offers, PaginatedDataCollection::class),
        ]);
    }

    /**
     * One offer: its terms, where it can be redeemed, and its change history.
     */
    public function show(VoucherOffer $offer): Response
    {
        $offer->load(['business', 'outlets.business']);

        return Inertia::render('admin/offers/show', [
            'offer' => OfferData::fromModel($offer),
            'sponsorCandidates' => HostOutletOptionData::collect(
                Outlet::operational()
                    ->where('business_id', '<>', $offer->business_id)
                    ->whereNotIn('id', $offer->outlets->modelKeys())
                    ->with('business')
                    ->orderBy('name')
                    ->get(),
            ),
            'activities' => Inertia::defer(fn () => ActivityData::collect(
                Activity::forSubject($offer)->with('causer')->latest('id')->limit(100)->get(),
            )),
        ]);
    }
}
