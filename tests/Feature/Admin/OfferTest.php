<?php

use App\Jobs\NotifyOfferModeration;
use App\Mail\OfferModerationMail;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\User;
use App\Models\VoucherOffer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admins list every business offer and filter the sponsored ones', function () {
    $sponsored = VoucherOffer::factory()->at(Outlet::factory()->create())->create();
    VoucherOffer::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.offers.index'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/offers/index')->has('offers.data', 2));

    $this->get(route('admin.offers.index', ['filter' => ['sponsored' => '1']]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('offers.data', 1)
            ->where('offers.data.0.offer.id', $sponsored->id)
            ->where('offers.data.0.is_sponsored', true));
});

test('members cannot open the admin offers', function () {
    $owner = Membership::factory()->owner()->create();

    $this->actingAs($owner->user)->get(route('admin.offers.index'))->assertForbidden();
});

test('the offer page lists trading outlets of other businesses as sponsor candidates', function () {
    $offer = VoucherOffer::factory()->for(Business::factory()->approved())->create();
    Outlet::factory()->for($offer->business)->approved()->create();
    $candidate = Outlet::factory()->publiclyVisible()->create();
    Outlet::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.offers.show', $offer))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/offers/show')
            ->has('sponsorCandidates', 1)
            ->where('sponsorCandidates.0.id', $candidate->id));
});

test('an admin hides an offer with a reason and its managers are told', function () {
    Queue::fake([NotifyOfferModeration::class]);
    $offer = VoucherOffer::factory()->active()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.offers.hide', $offer), ['reason' => 'Misleading terms.'])
        ->assertSessionHasNoErrors();

    expect($offer->refresh()->isHidden())->toBeTrue()
        ->and($offer->hidden_reason)->toBe('Misleading terms.')
        ->and(VoucherOffer::published()->exists())->toBeFalse();
    expect(Activity::forSubject($offer)->forEvent('hidden')->sole()->reason)->toBe('Misleading terms.');
    Queue::assertPushed(NotifyOfferModeration::class, fn (NotifyOfferModeration $job) => $job->offer->is($offer) && $job->action === 'hidden');
});

test('an admin restores a hidden offer', function () {
    Queue::fake([NotifyOfferModeration::class]);
    $offer = VoucherOffer::factory()->active()->hidden()->create();

    $this->actingAs($this->admin)->post(route('admin.offers.unhide', $offer))->assertSessionHasNoErrors();

    expect($offer->refresh()->isHidden())->toBeFalse();
    Queue::assertPushed(NotifyOfferModeration::class, fn (NotifyOfferModeration $job) => $job->action === 'unhidden');
});

test('the moderation email goes to the members who manage offers', function () {
    Mail::fake();
    $offer = VoucherOffer::factory()->create();
    $owner = Membership::factory()->owner()->for($offer->business)->create();
    $manager = Membership::factory()->manager()->for($offer->business)->create();
    Membership::factory()->cashier()->for($offer->business)->create();

    (new NotifyOfferModeration($offer, 'hidden', 'Misleading terms.'))->handle();

    Mail::assertSent(OfferModerationMail::class, 2);
    Mail::assertSent(OfferModerationMail::class, fn (OfferModerationMail $mail) => $mail->hasTo($owner->user->email));
    Mail::assertSent(OfferModerationMail::class, fn (OfferModerationMail $mail) => $mail->hasTo($manager->user->email));
});

test('an admin adds and removes a sponsored outlet', function () {
    $offer = VoucherOffer::factory()->create();
    $outlet = Outlet::factory()->for(Business::factory()->approved())->approved()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.offers.outlets.store', $offer), ['outlet_id' => $outlet->id])
        ->assertSessionHasNoErrors();

    expect($offer->outlets()->whereKey($outlet->id)->exists())->toBeTrue();
    expect(Activity::forSubject($offer)->forEvent('sponsored_outlet_added')->sole()->properties['outlet'])->toBe($outlet->name);

    $this->delete(route('admin.offers.outlets.destroy', [$offer, $outlet]))->assertSessionHasNoErrors();

    expect($offer->outlets()->exists())->toBeFalse();
});

test('a sponsored outlet must belong to another business and be trading', function (string $case) {
    $offer = VoucherOffer::factory()->for(Business::factory()->approved())->create();
    $outlet = match ($case) {
        'own outlet' => Outlet::factory()->for($offer->business)->approved()->create(),
        'not approved' => Outlet::factory()->for(Business::factory()->approved())->create(),
        'suspended' => Outlet::factory()->for(Business::factory()->approved())->approved()->suspended()->create(),
    };

    $this->actingAs($this->admin)
        ->post(route('admin.offers.outlets.store', $offer), ['outlet_id' => $outlet->id])
        ->assertSessionHasErrors('outlet_id');

    expect($offer->outlets()->exists())->toBeFalse();
})->with(['own outlet', 'not approved', 'suspended']);

test('an admin cannot remove an own outlet as if it were sponsored', function () {
    $offer = VoucherOffer::factory()->create();
    $own = Outlet::factory()->for($offer->business)->create();
    $offer->outlets()->attach($own);

    $this->actingAs($this->admin)
        ->delete(route('admin.offers.outlets.destroy', [$offer, $own]))
        ->assertSessionHasErrors('outlet_id');

    expect($offer->outlets()->exists())->toBeTrue();
});

test('owners cannot change sponsored outlets through the admin routes', function () {
    $owner = Membership::factory()->owner()->create();
    $offer = VoucherOffer::factory()->for($owner->business)->create();

    $this->actingAs($owner->user)
        ->post(route('admin.offers.outlets.store', $offer), ['outlet_id' => Outlet::factory()->create()->id])
        ->assertForbidden();
});
