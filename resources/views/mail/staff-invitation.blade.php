<x-mail::message>
# {{ __('Join :business', ['business' => $businessName]) }}

@if ($inviterName)
{{ __(':inviter invited you to join :business as :role.', ['inviter' => $inviterName, 'business' => $businessName, 'role' => $role]) }}
@else
{{ __('You are invited to join :business as :role.', ['business' => $businessName, 'role' => $role]) }}
@endif

<x-mail::button :url="$url">
{{ __('Review invitation') }}
</x-mail::button>

{{ __('This invitation expires on :date.', ['date' => $expiresAt->toFormattedDayDateString()]) }}

{{ config('app.name') }}
</x-mail::message>
