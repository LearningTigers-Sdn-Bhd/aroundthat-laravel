<x-mail::message>
@if ($action === 'hidden')
# {{ __(':offer was taken down', ['offer' => $offerName]) }}

{{ __('An admin hid the offer :offer. Guests cannot claim it and issued vouchers cannot be used until an admin restores it.', ['offer' => $offerName]) }}
@else
# {{ __(':offer can run again', ['offer' => $offerName]) }}

{{ __('An admin restored the offer :offer. If it is active and inside its dates, guests can claim and use it now.', ['offer' => $offerName]) }}
@endif

@if ($reason)
**{{ __('Reason') }}:** {{ $reason }}
@endif

<x-mail::button :url="$url">
{{ __('Open :offer', ['offer' => $offerName]) }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
