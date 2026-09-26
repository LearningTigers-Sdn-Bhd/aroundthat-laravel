<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value is the slug of an outlet visitors can see. The public slugs are looked up once for the whole request,
 * so a batch of events costs one query. A slug that is unknown and one that is not public read the same.
 */
class PublicOutletSlug implements ValidationRule
{
    /**
     * @param  list<string>  $publicSlugs  The public slugs among the ones in the request.
     */
    public function __construct(protected array $publicSlugs) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, $this->publicSlugs, true)) {
            $fail(__('The outlet is not available.'));
        }
    }
}
