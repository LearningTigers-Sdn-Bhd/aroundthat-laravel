<?php

namespace App\Support\Dashboard;

use App\Data\DashboardAttentionData;
use App\Enums\Ability;
use App\Enums\OfferStatus;
use App\Enums\OnboardingStatus;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;

/**
 * The dashboard's to-do list: the business or outlets not yet approved or listed, draft offers and unanswered
 * invitations. Each item shows only to members who can deal with it.
 */
class DashboardAttention
{
    /** Field names from Outlet::missingForListing() in words. */
    protected const array LISTING_FIELDS = [
        'summary' => 'summary',
        'category_id' => 'category',
        'coordinates' => 'map location',
        'hours' => 'opening hours',
    ];

    /**
     * @return list<DashboardAttentionData>
     */
    public function for(Membership $membership): array
    {
        $business = $membership->business;

        return array_values(array_filter([
            $membership->can(Ability::ManageBusiness) ? $this->business($business) : null,
            ...($membership->can(Ability::ManageOutlets) ? $this->outlets($business) : []),
            $membership->can(Ability::ManageOffers) ? $this->draftOffers($business) : null,
            $membership->can(Ability::ManageStaff) ? $this->invitations($business) : null,
        ]));
    }

    protected function business(Business $business): ?DashboardAttentionData
    {
        $url = route('business.edit');

        return match ($business->onboarding_status) {
            OnboardingStatus::Draft => new DashboardAttentionData(__('Submit your business for review'), __('Guests cannot see your outlets and offers until an admin approves your business.'), $url),
            OnboardingStatus::Rejected => new DashboardAttentionData(__('Your business was not approved'), $business->rejection_reason ?? __('Fix your details and submit again.'), $url),
            OnboardingStatus::Pending => new DashboardAttentionData(__('Your business is waiting for review'), __('An admin will look at it soon. You can still prepare outlets and offers.'), $url),
            default => null,
        };
    }

    /**
     * @return list<DashboardAttentionData|null>
     */
    protected function outlets(Business $business): array
    {
        return array_values($business->outlets()->whereNull('archived_at')->orderBy('name')->get()
            ->map(fn (Outlet $outlet): ?DashboardAttentionData => $this->outlet($outlet))
            ->all());
    }

    /**
     * The one thing an outlet needs next, if anything: to be submitted, fixed, completed or listed.
     */
    protected function outlet(Outlet $outlet): ?DashboardAttentionData
    {
        $url = route('outlets.edit', $outlet);
        $missing = array_map(fn (string $field): string => __(self::LISTING_FIELDS[$field] ?? $field), $outlet->missingForListing());

        return match (true) {
            $outlet->onboarding_status === OnboardingStatus::Draft => new DashboardAttentionData(__(':name is a draft', ['name' => $outlet->name]), __('Submit it for review so it can take vouchers.'), $url),
            $outlet->onboarding_status === OnboardingStatus::Rejected => new DashboardAttentionData(__(':name was not approved', ['name' => $outlet->name]), $outlet->rejection_reason ?? __('Fix its details and submit it again.'), $url),
            $outlet->onboarding_status !== OnboardingStatus::Approved || $outlet->isHidden() => null,
            $missing !== [] => new DashboardAttentionData(__(':name is missing details', ['name' => $outlet->name]), __('Add :fields to list it for guests.', ['fields' => implode(', ', $missing)]), $url),
            ! $outlet->is_listed => new DashboardAttentionData(__(':name is not listed', ['name' => $outlet->name]), __('It is ready. List it so guests can find it.'), $url),
            default => null,
        };
    }

    protected function draftOffers(Business $business): ?DashboardAttentionData
    {
        $count = $business->voucherOffers()->where('status', OfferStatus::Draft)->whereNull('hidden_at')->where('ends_at', '>', now())->count();

        return $count > 0
            ? new DashboardAttentionData(trans_choice('{1} :count draft offer|[2,*] :count draft offers', $count), __('Activate an offer when it is ready for guests.'), route('offers.index'))
            : null;
    }

    protected function invitations(Business $business): ?DashboardAttentionData
    {
        $count = $business->invitations()->pending()->count();

        return $count > 0
            ? new DashboardAttentionData(trans_choice('{1} :count invitation not answered|[2,*] :count invitations not answered', $count), __('Resend it if the person cannot find the email.'), route('staff.index'))
            : null;
    }
}
