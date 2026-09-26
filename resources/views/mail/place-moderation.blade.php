<x-mail::message>
@if ($action === 'reverted')
# {{ __('A change was reverted') }}

{{ __('An admin put :change of :place back to how it was before your last edit.', ['change' => $change, 'place' => $placeName]) }}
@elseif ($action === 'hidden')
# {{ __(':place is hidden from visitors', ['place' => $placeName]) }}

{{ __('An admin hid :place from the public listing. It keeps trading, but visitors cannot find it, and it cannot be listed again until an admin shows it.', ['place' => $placeName]) }}
@else
# {{ __(':place is visible again', ['place' => $placeName]) }}

{{ __('An admin showed :place on the public listing again. If it is listed, visitors can find it now.', ['place' => $placeName]) }}
@endif

@if ($reason)
**{{ __('Reason') }}:** {{ $reason }}
@endif

<x-mail::button :url="$url">
{{ __('Open :place', ['place' => $placeName]) }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
