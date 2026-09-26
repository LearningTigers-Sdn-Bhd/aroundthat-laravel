<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\ActivityData;
use App\Data\Admin\OfferData;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\VoucherOffer;
use Inertia\Inertia;
use Inertia\Response;

class OfferActivityController extends Controller
{
    /**
     * The Activity tab of an offer: the latest changes to it.
     */
    public function index(VoucherOffer $offer): Response
    {
        $offer->load(['business', 'outlets.business']);

        return Inertia::render('admin/offers/activity', [
            'offer' => OfferData::fromModel($offer),
            'activities' => Inertia::defer(fn () => ActivityData::collect(
                Activity::forSubject($offer)->with('causer')->latest('id')->limit(100)->get(),
            )),
        ]);
    }
}
