<?php

namespace App\Http\Requests\Api;

use App\Enums\EngagementEventType;
use App\Enums\OutboundDestination;
use App\Models\Outlet;
use App\Rules\PublicOutletSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A batch of engagement events. One invalid event rejects the whole batch, so a partner can safely resend it.
 */
class StoreEngagementEventsRequest extends FormRequest
{
    public const int MAX_EVENTS = 100;

    /**
     * RFC 3339 times, with or without fractions of a second. The timezone is required.
     */
    public const string TIMESTAMP_FORMATS = 'Y-m-d\\TH:i:sP,Y-m-d\\TH:i:s.uP';

    /**
     * Outlet ids keyed by slug, for the public outlets the batch names.
     *
     * @var array<string, string>|null
     */
    protected ?array $publicOutletIds = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:'.self::MAX_EVENTS],
            'events.*' => ['array'],
            'events.*.event_type' => ['required', Rule::enum(EngagementEventType::class)],
            /** The outlet's slug. */
            'events.*.outlet' => ['required', 'string', new PublicOutletSlug(array_keys($this->publicOutletIds()))],
            /** Your own reference for the visitor's session. It is stored only as a one-way hash. */
            'events.*.session_ref' => ['required', 'string', 'max:255'],
            /** Your own event ID. An ID you already sent is skipped, so resending a batch never counts twice. */
            'events.*.external_event_id' => ['nullable', 'string', 'max:255'],
            /** When it happened, as an RFC 3339 time with a timezone. */
            'events.*.occurred_at' => ['required', 'string', 'date_format:'.self::TIMESTAMP_FORMATS],
            /** Only for `outbound_click`. */
            'events.*.metadata' => ['prohibited_unless:events.*.event_type,outbound_click', 'required_if:events.*.event_type,outbound_click', 'array:destination_type'],
            'events.*.metadata.destination_type' => ['required_with:events.*.metadata', Rule::enum(OutboundDestination::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'events.max' => __('Send at most :max events in one batch.'),
            'events.*.occurred_at.date_format' => __('Use an RFC 3339 time with a timezone, such as 2026-09-01T08:30:00+08:00.'),
            'events.*.metadata.prohibited_unless' => __('Only outbound clicks have metadata.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function publicOutletIds(): array
    {
        if ($this->publicOutletIds !== null) {
            return $this->publicOutletIds;
        }

        $events = $this->input('events');
        $slugs = is_array($events)
            ? array_values(array_unique(array_filter(
                array_map(fn (mixed $event): mixed => is_array($event) ? ($event['outlet'] ?? null) : null, $events),
                'is_string',
            )))
            : [];

        /** @var array<string, string> $ids */
        $ids = $slugs === []
            ? []
            : Outlet::query()->public()->whereIn('outlets.slug', $slugs)->pluck('outlets.id', 'outlets.slug')->all();

        return $this->publicOutletIds = $ids;
    }
}
