<?php

namespace App\Http\Resources;

use App\Enums\ImageKind;
use App\Models\Image;
use App\Models\Outlet;
use App\Models\OutletDateException;
use App\Support\OpeningHours;
use Illuminate\Http\Request;

/**
 * A public outlet with everything a place page needs. Also load `business.images` (active) first.
 *
 * @mixin Outlet
 */
class OutletDetailResource extends OutletResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $today = now()->setTimezone($this->timezone)->toDateString();
        /** @var Image|null $logo */
        $logo = $this->business->images->first(fn (Image $image): bool => $image->kind === ImageKind::Logo);

        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'contact' => [
                'phone' => $this->contact_phone,
                'email' => $this->contact_email,
                'website' => $this->website,
                'whatsapp' => $this->whatsapp,
                'facebook' => $this->facebook,
                'instagram' => $this->instagram,
                'google_maps_url' => $this->google_maps_url,
            ],
            /**
             * Local opening periods keyed by ISO weekday, "1" for Monday to "7" for Sunday. A closing time of 00:00 means
             * the end of the day; overnight hours are split at midnight.
             *
             * @var array<string, list<array{opens: string, closes: string}>>
             */
            'regular_hours' => collect(OpeningHours::DAYS)
                ->mapWithKeys(fn (int $day): array => [(string) $day => $this->regular_hours[$day] ?? []])
                ->all(),
            /** Today and later dates with different hours, such as public holidays. */
            'date_exceptions' => $this->dateExceptions
                ->filter(fn (OutletDateException $exception): bool => $exception->date->toDateString() >= $today)
                ->map(fn (OutletDateException $exception): array => [
                    'date' => $exception->date->toDateString(),
                    'is_closed' => $exception->is_closed,
                    'periods' => $exception->is_closed ? [] : ($exception->periods ?? []),
                    'note' => $exception->note,
                ])
                ->values()
                ->all(),
            /** The cover first, then the gallery in order. */
            'images' => ImageResource::collection($this->images),
            'business' => [
                'slug' => $this->business->slug,
                'name' => $this->business->name,
                'summary' => $this->business->summary,
                'description' => $this->business->description,
                'logo' => $logo ? new ImageResource($logo) : null,
            ],
        ];
    }
}
